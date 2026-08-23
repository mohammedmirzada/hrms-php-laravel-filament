<?php

use App\Models\Meraki;
use App\Models\MerakiUser;
use App\Support\MerakiSettings;
use Illuminate\Support\Facades\DB;

/*
 * Runs against the real dev DB but under a fake serial number, so nothing
 * real is touched. Everything is deleted again at the end.
 */

const SN = 'TEST-SN-0001';

$savedSettings = null;

beforeEach(function () use (&$savedSettings) {
    // Stop dead if we are not on the throwaway sqlite database. A cached
    // bootstrap/cache/config.php beats phpunit.xml and once pointed these
    // tests at the real MySQL database.
    if (DB::connection()->getDatabaseName() !== ':memory:') {
        throw new RuntimeException(
            'These tests must run on in-memory sqlite, not '
            . DB::connection()->getDatabaseName()
            . '. Run: php artisan config:clear'
        );
    }

    // Only the two attendance tables, on the in-memory sqlite DB. The rest of
    // the app's migrations are not needed here and some do not run on sqlite.
    (require database_path('migrations/2026_08_22_000000_create_miraki_tables.php'))->up();
    (require database_path('migrations/2026_08_23_000000_rename_miraki_tables_to_meraki.php'))->up();
    (require database_path('migrations/2026_08_25_000000_add_shift_id_to_meraki.php'))->up();

    config()->set('meraki.clients.meraki.device_sn', SN);
    config()->set('meraki.auth.username', 'tester');
    config()->set('meraki.auth.password', 'secret');

    // Put the real saved shift aside, so running tests never wipes it.
    $file = storage_path('app/meraki-settings.json');

    $savedSettings = is_file($file) ? file_get_contents($file) : null;

    @unlink($file);
});

afterEach(function () use (&$savedSettings) {
    $file = storage_path('app/meraki-settings.json');

    if ($savedSettings === null) {
        @unlink($file);
    } else {
        file_put_contents($file, $savedSettings);
    }
});

function punch(string $pin, string $at, int $status): void {
    Meraki::create([
        'device_sn' => SN, 'pin' => $pin, 'punched_at' => $at,
        'status' => $status, 'verify' => 1, 'raw' => 'test',
    ]);
}

function loggedIn() {
    return test()->withSession(['meraki_auth:meraki' => true]);
}

/** The "Worked" values out of the calendar cells, in page order. */
function worked(string $html): array {
    preg_match_all('/class="line worked">.*?class="val">([^<]*)</s', $html, $m);

    return array_map('trim', $m[1]);
}

/** How many person rows start opened instead of collapsed. */
function opened(string $html): int {
    return preg_match_all('/<details class="one[^"]*"\s+open/', $html);
}

/** The "Extra" (overtime) values out of the calendar cells. */
function extra(string $html): array {
    preg_match_all('/class="line over">.*?class="val">([^<]*)</s', $html, $m);

    return array_map('trim', $m[1]);
}

it('shows the login page and rejects a wrong password', function () {
    $this->get('/meraki/report')->assertRedirect('/meraki/login');

    $this->get('/meraki/login')
        ->assertOk()
        ->assertSee('Log in')
        ->assertSee('name="password"', false);

    $this->post('/meraki/login', ['username' => 'tester', 'password' => 'nope'])
        ->assertOk()
        ->assertSee('Wrong username or password.');
});

it('logs in with the right password and reaches the calendar', function () {
    $this->post('/meraki/login', ['username' => 'tester', 'password' => 'secret'])
        ->assertRedirect('/meraki/report');

    $this->get('/meraki/report')->assertOk()->assertSee('Calendar');
});

it('refuses to log in when the password is not configured', function () {
    config()->set('meraki.auth.password', '');

    $this->post('/meraki/login', ['username' => 'tester', 'password' => 'secret'])
        ->assertStatus(503);
});

it('adds up in/out pairs and does not pay the break', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ahmed', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    punch('1', "$day 08:00:00", 0);   // in
    punch('1', "$day 12:00:00", 1);   // out
    punch('1', "$day 13:00:00", 0);   // in
    punch('1', "$day 17:00:00", 1);   // out

    $html = loggedIn()->get('/meraki/report?pin=1')->assertOk()->getContent();

    expect($html)->toContain('Ahmed');
    expect(worked($html))->toBe(['8 hours']);   // 9h on site minus 1h lunch
    expect(extra($html))->toBe([]);             // 8h worked, 9h work day
});

it('counts overtime as worked hours over the shift length', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '2', 'name' => 'Sara', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    punch('2', "$day 07:00:00", 0);
    punch('2', "$day 19:00:00", 1);   // 12h worked, shift is 9h

    $html = loggedIn()->get('/meraki/report?pin=2')->assertOk()->getContent();

    expect(worked($html))->toBe(['12 hours']);
    expect(extra($html))->toBe(['3 hours']);
});

it('marks a day where the check out is missing', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '3', 'name' => 'Ali', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    punch('3', "$day 08:00:00", 0);   // in, never out

    $html = loggedIn()->get('/meraki/report?pin=3')->assertOk()->getContent();

    expect($html)->toContain('Missing a check out');
    expect($html)->toContain('never pressed');   // no out time to show
    expect(worked($html))->toBe(['0 min']);
});

it('says no check in when the day starts with an OUT', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '7', 'name' => 'Hussein', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    punch('7', "$day 17:00:00", 1);   // out, with no in before it

    $html = loggedIn()->get('/meraki/report?pin=7')->assertOk()->getContent();

    expect($html)->toContain('Missing a check in');
});

it('does not cry about a check out pressed twice', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '8', 'name' => 'Kareem', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    punch('8', "$day 08:00:00", 0);   // in
    punch('8', "$day 17:00:00", 1);   // out
    punch('8', "$day 17:00:03", 1);   // finger read again, 3 seconds later

    $html = loggedIn()->get('/meraki/report?pin=8')->assertOk()->getContent();

    expect(worked($html))->toBe(['9 hours']);
    expect($html)->not->toContain('Missing');   // nothing is actually missing
});

it('ignores a repeated IN instead of turning it into an OUT', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '4', 'name' => 'Zaid', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    punch('4', "$day 08:00:00", 0);   // in
    punch('4', "$day 08:05:00", 0);   // in again by mistake
    punch('4', "$day 17:00:00", 1);   // out

    $html = loggedIn()->get('/meraki/report?pin=4')->assertOk()->getContent();

    expect(worked($html))->toBe(['9 hours']);   // 08:00 -> 17:00, not 08:05
});

it('saves a new shift from the settings page and uses it', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '5', 'name' => 'Omar', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    punch('5', "$day 08:00:00", 0);
    punch('5', "$day 16:00:00", 1);   // 8h worked

    expect(extra(loggedIn()->get('/meraki/report?pin=5')->getContent()))->toBe([]);

    // A new shift, then move him onto it — the hours of shift 1 are fixed
    loggedIn()->post('/meraki/settings/shifts', ['shifts' => [
        ['id' => '',  'name' => 'Short day', 'start' => '9:00', 'end' => '15:00', 'grace' => 5],
    ]])->assertRedirect('/meraki/settings?saved=1');

    loggedIn()->post('/meraki/settings/people', ['people' => ['5' => '2']]);

    $shift = MerakiSettings::shift('meraki', '2');

    expect($shift['name'])->toBe('Short day');
    expect($shift['start'])->toBe('09:00');
    expect($shift['end'])->toBe('15:00');
    expect(MerakiSettings::shiftMinutes('meraki', '2'))->toBe(360);

    $html = loggedIn()->get('/meraki/report?pin=5')->assertOk()->getContent();

    expect(extra($html))->toBe(['2 hours']);   // 8h worked, 6h work day
});

it('changes a shift and adds another in one save', function () {
    loggedIn()->post('/meraki/settings/shifts', ['shifts' => [
        ['id' => '1', 'name' => 'Office hours', 'start' => '08:00', 'end' => '16:00', 'grace' => 5],
        ['id' => '',  'name' => 'Night shift',  'start' => '20:00', 'end' => '05:00', 'grace' => 10],
        ['id' => '',  'name' => '',             'start' => '',      'end' => '',      'grace' => ''],
    ]])->assertRedirect('/meraki/settings?saved=1');

    $shifts = MerakiSettings::shifts('meraki');

    // The blank row at the bottom is skipped, not saved as a third shift
    expect($shifts)->toHaveCount(2);

    // Shift 1 was renamed, but its hours are fixed once saved
    expect($shifts['1']['name'])->toBe('Office hours');
    expect($shifts['1']['minutes'])->toBe(540);     // still 08:00 -> 17:00

    expect($shifts['2']['name'])->toBe('Night shift');
    expect($shifts['2']['minutes'])->toBe(540);     // 20:00 -> 05:00, just hours
    expect($shifts['2']['grace'])->toBe(10);

    loggedIn()->get('/meraki/settings')->assertOk()
        ->assertSee('Office hours')
        ->assertSee('Night shift');
});

it('saves none of the rows when one of them is wrong', function () {
    loggedIn()->post('/meraki/settings/shifts', ['shifts' => [
        ['id' => '1', 'name' => 'Office hours', 'start' => '08:00', 'end' => '16:00', 'grace' => 5],
        ['id' => '',  'name' => '  ',           'start' => '20:00', 'end' => '05:00', 'grace' => 0],
    ]])->assertOk()->assertSee('Could not save the shifts');

    $shifts = MerakiSettings::shifts('meraki');

    // Still the one untouched shift — the good row was not applied either
    expect($shifts)->toHaveCount(1);
    expect($shifts['1']['name'])->toBe('Day shift');
    expect($shifts['1']['minutes'])->toBe(540);
});

it('does not drop a shift that was left out of the list', function () {
    loggedIn()->post('/meraki/settings/shifts', ['shifts' => [
        ['id' => '', 'name' => 'Half day', 'start' => '08:00', 'end' => '13:00', 'grace' => 0],
    ]]);

    expect(MerakiSettings::shifts('meraki'))->toHaveCount(2);

    // Send only the new one back; the first must survive
    loggedIn()->post('/meraki/settings/shifts', ['shifts' => [
        ['id' => '2', 'name' => 'Morning', 'start' => '08:00', 'end' => '12:00', 'grace' => 0],
    ]]);

    $shifts = MerakiSettings::shifts('meraki');

    expect($shifts)->toHaveCount(2);
    expect($shifts['1']['name'])->toBe('Day shift');
    expect($shifts['2']['name'])->toBe('Morning');
    expect($shifts['2']['minutes'])->toBe(300);   // 08:00-13:00, hours are fixed
});

it('measures a person against the shift they are put on', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ahmed', 'privilege' => 0]);
    MerakiUser::create(['device_sn' => SN, 'pin' => '2', 'name' => 'Sara', 'privilege' => 0]);

    // Shift 2 is a six hour day
    loggedIn()->post('/meraki/settings/shifts', ['shifts' => [
        ['id' => '', 'name' => 'Half day', 'start' => '08:00', 'end' => '14:00', 'grace' => 0],
    ]]);

    loggedIn()->post('/meraki/settings/people', ['people' => ['2' => '2']]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    // Both work exactly eight hours
    foreach (['1', '2'] as $pin) {
        punch($pin, "$day 08:00:00", 0);
        punch($pin, "$day 16:00:00", 1);
    }

    expect(MerakiSettings::shiftFor('meraki', '1')['name'])->toBe('Day shift');
    expect(MerakiSettings::shiftFor('meraki', '2')['name'])->toBe('Half day');

    // Ahmed is 1h short of nine, Sara is 2h over six
    expect(extra(loggedIn()->get('/meraki/report?pin=1')->getContent()))->toBe([]);
    expect(extra(loggedIn()->get('/meraki/report?pin=2')->getContent()))->toBe(['2 hours']);

    loggedIn()->get('/meraki/overtime')->assertOk()
        ->assertSee('Half day')
        ->assertSee('Day shift');
});

it('lets the grace period forgive a few minutes short', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ahmed', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    punch('1', "$day 08:04:00", 0);   // four minutes late
    punch('1', "$day 17:00:00", 1);   // 8h 56m, four short of nine hours

    // The default shift already gives five minutes of grace
    $html = loggedIn()->get('/meraki/overtime')->assertOk()->getContent();

    expect($html)->toContain('8 hours 56 min');
    expect(substr_count($html, 'class="n zero"'))->toBe(3);   // no short time

    // The same day on a shift with no grace is four minutes short
    loggedIn()->post('/meraki/settings/shifts', ['shifts' => [
        ['id' => '', 'name' => 'No grace', 'start' => '08:00', 'end' => '17:00', 'grace' => 0],
    ]]);
    loggedIn()->post('/meraki/settings/people', ['people' => ['1' => '2']]);

    expect(loggedIn()->get('/meraki/overtime')->getContent())->toContain('4 min');
});

it('keeps who is on which shift when a shift is edited', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '2', 'name' => 'Sara', 'privilege' => 0]);

    loggedIn()->post('/meraki/settings/shifts', ['shifts' => [
        ['id' => '', 'name' => 'Night shift', 'start' => '20:00', 'end' => '05:00', 'grace' => 0],
    ]]);

    loggedIn()->post('/meraki/settings/people', ['people' => ['2' => '2']]);

    loggedIn()->post('/meraki/settings/shifts', ['shifts' => [
        ['id' => '2', 'name' => 'Late shift', 'start' => '21:00', 'end' => '05:00', 'grace' => 0],
    ]]);

    expect(MerakiSettings::people('meraki'))->toBe(['2' => '2']);
    expect(MerakiSettings::shiftFor('meraki', '2')['name'])->toBe('Late shift');
    expect(MerakiSettings::shiftFor('meraki', '2')['start'])->toBe('20:00');   // hours untouched
});

it('treats a made up shift id as a new shift, not a crash', function () {
    loggedIn()->post('/meraki/settings/shifts', ['shifts' => [
        ['id' => '99', 'name' => 'Ghost', 'start' => '08:00', 'end' => '17:00', 'grace' => 0],
    ]])->assertRedirect('/meraki/settings?saved=1');

    $shifts = MerakiSettings::shifts('meraki');

    // Added as the next real id, never as 99
    expect($shifts)->toHaveCount(2);
    expect(array_keys($shifts))->toBe([1, 2]);
    expect($shifts['2']['name'])->toBe('Ghost');

    // A person still cannot be put on an id that does not exist
    loggedIn()->post('/meraki/settings/people', ['people' => ['2' => '99']]);

    expect(MerakiSettings::people('meraki'))->toBe([]);
});

it('keeps the day box short when everybody is shown', function () {
    $day = now()->startOfMonth()->addDays(9)->toDateString();

    // Ten people all working the same day
    foreach (range(1, 10) as $n) {
        MerakiUser::create(['device_sn' => SN, 'pin' => (string) $n, 'name' => "Person {$n}", 'privilege' => 0]);
        punch((string) $n, "$day 08:00:00", 0);
        punch((string) $n, "$day 17:00:00", 1);
    }

    $html = loggedIn()->get('/meraki/report')->assertOk()->getContent();

    // One row each, and every one of them starts closed
    expect(substr_count($html, '<details class="one'))->toBe(10);
    expect(opened($html))->toBe(0);

    // Six rows in the box, the other four folded behind "+4 more"
    expect($html)->toContain('+4 more');
    expect(substr_count($html, '<details class="more">'))->toBe(1);
});

it('opens the full block when one person is picked', function () {
    $day = now()->startOfMonth()->addDays(9)->toDateString();

    foreach (range(1, 10) as $n) {
        MerakiUser::create(['device_sn' => SN, 'pin' => (string) $n, 'name' => "Person {$n}", 'privilege' => 0]);
        punch((string) $n, "$day 08:00:00", 0);
        punch((string) $n, "$day 17:00:00", 1);
    }

    $html = loggedIn()->get('/meraki/report?pin=3')->assertOk()->getContent();

    expect(worked($html))->toBe(['9 hours']);     // just that person
    expect($html)->toContain('First in');
    expect(opened($html))->toBe(1);               // already open, no clicking
    expect($html)->not->toContain('more</summary>');   // nothing folded away
});

it('totals extra time and short time per person', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ahmed', 'privilege' => 0]);
    MerakiUser::create(['device_sn' => SN, 'pin' => '2', 'name' => 'Sara', 'privilege' => 0]);

    $d1 = now()->startOfMonth()->addDays(9)->toDateString();
    $d2 = now()->startOfMonth()->addDays(10)->toDateString();

    // Ahmed: 12h then 9h  -> 3h extra, nothing short
    punch('1', "$d1 07:00:00", 0);
    punch('1', "$d1 19:00:00", 1);
    punch('1', "$d2 08:00:00", 0);
    punch('1', "$d2 17:00:00", 1);

    // Sara: 7h then 9h30  -> 30 min extra, 2h short
    punch('2', "$d1 09:00:00", 0);
    punch('2', "$d1 16:00:00", 1);
    punch('2', "$d2 08:00:00", 0);
    punch('2', "$d2 17:30:00", 1);

    $page = loggedIn()->get('/meraki/overtime')->assertOk();

    $page->assertSee('Ahmed')->assertSee('Sara');
    $page->assertSee('3 hours');            // Ahmed extra
    $page->assertSee('30 min');             // Sara extra
    $page->assertSee('1 hour 55 min');      // Sara short — 2h less the 5 min grace
    $page->assertSee('21 hours');           // Ahmed worked 12h + 9h
});

it('counts the days that need checking on the overtime page', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ahmed', 'privilege' => 0]);

    $d1 = now()->startOfMonth()->addDays(9)->toDateString();
    $d2 = now()->startOfMonth()->addDays(10)->toDateString();

    punch('1', "$d1 08:00:00", 0);   // in, never out
    punch('1', "$d2 08:00:00", 0);   // same again the next day
    punch('1', "$d2 17:00:00", 1);

    $page = loggedIn()->get('/meraki/overtime')->assertOk();

    // A count in the totals table, not a second table underneath it
    $page->assertSee('Days to check');
    $page->assertDontSee('What is wrong');
    $page->assertDontSee('Missing a check out');
});

it('does not count a day nobody came as short time', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ahmed', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    punch('1', "$day 08:00:00", 0);
    punch('1', "$day 17:00:00", 1);   // exactly the 9h shift, one day only

    $html = loggedIn()->get('/meraki/overtime')->assertOk()->getContent();

    // One day came, and extra / short / days-to-check are all empty.
    // The other 20-odd days of the month are days off, not short time.
    expect($html)->toContain('9 hours');
    expect(substr_count($html, 'class="n zero"'))->toBe(3);
});

it('shows the calendar arrow and the colour key', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ahmed', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    punch('1', "$day 08:00:00", 0);
    punch('1', "$day 17:00:00", 1);

    $html = loggedIn()->get('/meraki/report')->assertOk()->getContent();

    expect($html)->toContain('class="caret"');       // the arrow
    expect($html)->toContain('Click a name');        // the key, in short lines
    expect($html)->toContain('class="dot g"');
    expect($html)->toContain('class="dot o"');
    expect($html)->toContain('class="dot r"');
});

it('gives the same in/out whether one person or everyone is shown', function () {
    // Ahmed has real device states, Sara's device was not sending any that day.
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ahmed', 'privilege' => 0]);
    MerakiUser::create(['device_sn' => SN, 'pin' => '2', 'name' => 'Sara', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    punch('1', "$day 08:00:00", 0);
    punch('1', "$day 17:00:00", 1);   // a real state, so Ahmed is on device mode

    punch('2', "$day 08:00:00", 0);
    punch('2', "$day 17:00:00", 0);   // all zeros, so Sara has to be counted

    $alone    = worked(loggedIn()->get('/meraki/report?pin=2')->getContent());
    $together = loggedIn()->get('/meraki/report')->getContent();

    // Alone, Sara is 9 hours. Together she must still be 9 hours — Ahmed's
    // states must not change how Sara's day is read.
    expect($alone)->toBe(['9 hours']);
    expect($together)->toContain('Sara');
    expect(substr_count($together, '9 hours'))->toBeGreaterThanOrEqual(2);
    expect($together)->not->toContain('Missing');
});

it('refuses a shift that starts and ends at the same time', function () {
    loggedIn()->post('/meraki/settings/shifts', ['shifts' => [
        ['id' => '', 'name' => 'All day', 'start' => '08:00', 'end' => '08:00', 'grace' => 0],
    ]])->assertOk()->assertSee('cannot start and end at the same time');

    expect(MerakiSettings::shifts('meraki'))->toHaveCount(1);
});

it('sends you back to the page you asked for after logging in', function () {
    $this->get('/meraki/overtime')->assertRedirect('/meraki/login');

    $this->post('/meraki/login', ['username' => 'tester', 'password' => 'secret'])
        ->assertRedirect('/meraki/overtime');
});

it('says so when a month has nothing in it', function () {
    loggedIn()->get('/meraki/report?month=2019-01')
        ->assertOk()
        ->assertSee('Nothing recorded');
});

it('marks a date nobody punched as a day off', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ahmed', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9);

    punch('1', $day->toDateString() . ' 08:00:00', 0);
    punch('1', $day->toDateString() . ' 17:00:00', 1);

    $html = loggedIn()->get('/meraki/report')->assertOk()->getContent();

    // Greyed, with no label written in the box. Only days already gone
    // count — the rest of the month has not happened yet.
    $gone = now()->day;

    expect(substr_count($html, '<div class="day rest">'))->toBe($gone - 1);
    expect(substr_count($html, 'rest-tag'))->toBe(0);
});

it('does not turn other people\'s work days into days off when filtering', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ahmed', 'privilege' => 0]);
    MerakiUser::create(['device_sn' => SN, 'pin' => '2', 'name' => 'Sara', 'privilege' => 0]);

    $d1 = now()->startOfMonth()->addDays(9)->toDateString();
    $d2 = now()->startOfMonth()->addDays(10)->toDateString();

    // Ahmed works day one, Sara works day two
    punch('1', "$d1 08:00:00", 0);
    punch('1', "$d1 17:00:00", 1);
    punch('2', "$d2 08:00:00", 0);
    punch('2', "$d2 17:00:00", 1);

    $everyone = loggedIn()->get('/meraki/report')->getContent();
    $justSara = loggedIn()->get('/meraki/report?pin=2')->getContent();

    // Two working days either way. Ahmed's day must not become a day off
    // just because Sara was filtered in.
    $gone = now()->day;

    expect(substr_count($everyone, '<div class="day rest">'))->toBe($gone - 2);
    expect(substr_count($justSara, '<div class="day rest">'))->toBe($gone - 2);
});

it('cuts the punch list into pages of 100', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ahmed', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    // 120 punches, one a minute from 08:00
    foreach (range(0, 119) as $i) {
        punch('1', sprintf('%s %02d:%02d:00', $day, 8 + intdiv($i, 60), $i % 60), $i % 2);
    }

    $one = loggedIn()->get('/meraki/log')->assertOk();

    $one->assertSee('Showing');
    expect($one->getContent())->toContain('<b>120</b>');
    $one->assertSee('Page 1 of 2');
    $one->assertSee('Older');

    expect(substr_count($one->getContent(), '<td class="who">'))->toBe(100);

    $two = loggedIn()->get('/meraki/log?page=2')->assertOk();

    $two->assertSee('Page 2 of 2');
    expect(substr_count($two->getContent(), '<td class="who">'))->toBe(20);
});

it('keeps the date and employee filters while paging', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ahmed', 'privilege' => 0]);
    MerakiUser::create(['device_sn' => SN, 'pin' => '2', 'name' => 'Sara', 'privilege' => 0]);

    $day   = now()->startOfMonth()->addDays(9)->toDateString();
    $first = now()->startOfMonth()->toDateString();
    $last  = now()->endOfMonth()->toDateString();

    foreach (range(0, 119) as $i) {
        punch('1', sprintf('%s %02d:%02d:00', $day, 8 + intdiv($i, 60), $i % 60), $i % 2);
    }

    punch('2', "$day 08:00:00", 0);   // Sara, filtered out

    $html = loggedIn()->get("/meraki/log?from={$first}&to={$last}&pins[]=1")
        ->assertOk()->getContent();

    expect($html)->toContain('pins%5B0%5D=1');
    expect($html)->toContain('from=' . $first);
    expect($html)->toContain('<b>120</b>');   // Sara's punch is not counted
});

it('does not fall over on a page number past the end', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ahmed', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    punch('1', "$day 08:00:00", 0);

    loggedIn()->get('/meraki/log?page=999')
        ->assertOk()
        ->assertSee('Page 1 of 1');
});

it('does not grey out days that have not happened yet', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ahmed', 'privilege' => 0]);

    $today = now()->startOfDay();

    punch('1', $today->toDateString() . ' 08:00:00', 0);
    punch('1', $today->toDateString() . ' 17:00:00', 1);

    $html = loggedIn()->get('/meraki/report')->assertOk()->getContent();

    // Only the days already gone, never the rest of the month
    $past = $today->day - 1;

    expect(substr_count($html, '<div class="day rest">'))->toBe($past);

    $flat = preg_replace('/\s+/', ' ', $html);

    expect($flat)->toContain('those ' . $past . ' days');
});

it('writes hours and minutes in words', function () {
    expect(MerakiSettings::readable(0))->toBe('0 min');
    expect(MerakiSettings::readable(45))->toBe('45 min');
    expect(MerakiSettings::readable(60))->toBe('1 hour');
    expect(MerakiSettings::readable(65))->toBe('1 hour 5 min');
    expect(MerakiSettings::readable(243))->toBe('4 hours 3 min');
    expect(MerakiSettings::readable(480))->toBe('8 hours');
});

it('reads an end before the start as hours that wrap past midnight', function () {
    loggedIn()->post('/meraki/settings/shifts', ['shifts' => [
        ['id' => '', 'name' => 'Late shift', 'start' => '22:00', 'end' => '06:00', 'grace' => 0],
    ]]);

    // Eight hours, and nothing else about it is treated differently
    expect(MerakiSettings::shiftMinutes('meraki', '2'))->toBe(480);
});

it('rejects a rubbish shift time', function () {
    loggedIn()->post('/meraki/settings/shifts', ['shifts' => [
        ['id' => '', 'name' => 'Nonsense', 'start' => 'banana', 'end' => '17:00', 'grace' => 0],
    ]])->assertOk()->assertSee('Could not save');

    expect(MerakiSettings::shifts('meraki'))->toHaveCount(1);
});

it('still shows the plain punch list newest first', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '6', 'name' => 'Nour', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    punch('6', "$day 08:00:00", 0);
    punch('6', "$day 17:30:00", 1);

    $html = loggedIn()->get('/meraki/log')->assertOk()->getContent();

    expect(strpos($html, '5:30 PM'))->toBeLessThan(strpos($html, '8:00 AM'));
});

it('puts the 1st of the month in the right column', function () {
    // Weeks start Saturday. 1 Sep 2026 is a Tuesday, so the grid opens with
    // 29, 30, 31 August and then the 1st in the fourth column.
    $html = loggedIn()->get('/meraki/report?month=2026-09')->assertOk()->getContent();

    preg_match_all('/class="num">(\d+)</', $html, $m);

    expect(array_slice($m[1], 0, 4))->toBe(['29', '30', '31', '1']);
});

it('shows the whole month as full weeks', function () {
    $html = loggedIn()->get('/meraki/report?month=' . now()->format('Y-m'))->assertOk()->getContent();

    // seven column headings, and every day box closed
    expect(substr_count($html, 'class="head"'))->toBe(7);
    expect(substr_count($html, 'class="day '))->toBeIn([35, 42]);
});

// ------------------------------------------------------------ date range

it('opens on this month when no dates are given', function () {
    $html = loggedIn()->get('/meraki/report')->assertOk()->getContent();

    expect($html)->toContain('value="' . now()->startOfMonth()->toDateString() . '"');
    expect($html)->toContain('value="' . now()->endOfMonth()->toDateString() . '"');
    expect($html)->toContain(now()->format('F Y'));
});

it('still understands an old month link', function () {
    $html = loggedIn()->get('/meraki/report?month=2026-09')->assertOk()->getContent();

    expect($html)->toContain('value="2026-09-01"');
    expect($html)->toContain('value="2026-09-30"');
});

it('shows only the days inside the range', function () {
    // Two whole months back, so nothing here is in the future
    $start = now()->subMonths(2)->startOfMonth();

    $from = $start->copy()->addDays(9);    // the 10th
    $to   = $start->copy()->addDays(19);   // the 20th

    $html = loggedIn()->get('/meraki/report?from=' . $from->toDateString() . '&to=' . $to->toDateString())
        ->assertOk()->getContent();

    $cells = substr_count($html, 'class="day ');
    $pad   = substr_count($html, '<div class="day pad">');

    // Eleven days asked for; the rest of the weeks are padding
    expect($cells - $pad)->toBe(11);

    // Nobody punched, so all eleven are days off
    expect(substr_count($html, '<div class="day rest">'))->toBe(11);
    expect(preg_replace('/\s+/', ' ', $html))->toContain('those 11 days');
});

it('shows a range that crosses two months', function () {
    $from = now()->subMonths(2)->startOfMonth()->addDays(24);   // the 25th
    $to   = now()->subMonth()->startOfMonth()->addDays(4);      // the 5th

    $html = loggedIn()->get('/meraki/report?from=' . $from->toDateString() . '&to=' . $to->toDateString())
        ->assertOk()->getContent();

    // the 1st of the later month is labelled, so nobody loses their place
    expect(substr_count($html, 'class="mon"'))->toBe(1);
    expect($html)->toContain($from->format('j M Y') . ' – ' . $to->format('j M Y'));
});

it('turns a backwards range the right way round', function () {
    $a = now()->startOfMonth()->addDays(4)->toDateString();
    $b = now()->startOfMonth()->addDays(14)->toDateString();

    $html = loggedIn()->get("/meraki/report?from={$b}&to={$a}")->assertOk()->getContent();

    expect($html)->toContain('name="from" value="' . $a . '"');
    expect($html)->toContain('name="to" value="' . $b . '"');
});

it('ignores a rubbish date and falls back to this month', function () {
    $html = loggedIn()->get('/meraki/report?from=banana&to=')->assertOk()->getContent();

    expect($html)->toContain('value="' . now()->startOfMonth()->toDateString() . '"');
});

it('counts only the punches inside the range', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ahmed', 'privilege' => 0]);

    $early = now()->startOfMonth()->addDays(2);
    $late  = now()->startOfMonth()->addDays(20);

    punch('1', $early->toDateString() . ' 08:00:00', 0);
    punch('1', $early->toDateString() . ' 17:00:00', 1);
    punch('1', $late->toDateString() . ' 08:00:00', 0);
    punch('1', $late->toDateString() . ' 17:00:00', 1);

    $narrow = '?from=' . now()->startOfMonth()->toDateString()
        . '&to=' . now()->startOfMonth()->addDays(9)->toDateString();

    // Calendar: one day in that range, not two
    expect(worked(loggedIn()->get('/meraki/report' . $narrow . '&pins[]=1')->getContent()))
        ->toBe(['9 hours']);

    // Overtime totals follow the same range
    expect(loggedIn()->get('/meraki/overtime' . $narrow)->getContent())
        ->toContain('9 hours');

    // Punch list too — two punches, not four
    expect(loggedIn()->get('/meraki/log' . $narrow)->getContent())
        ->toContain('<b>2</b>');
});

// -------------------------------------------------------- employee filter

it('shows the employee tick list instead of a dropdown', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ahmed', 'privilege' => 0]);

    foreach (['/meraki/report', '/meraki/overtime', '/meraki/log'] as $page) {
        $html = loggedIn()->get($page)->assertOk()->getContent();

        expect($html)->toContain('Employee');
        expect($html)->toContain('name="pins[]"');
        expect($html)->toContain('Tick nobody to see everyone');
    }
});

it('shows several employees at once, all opened', function () {
    $day = now()->startOfMonth()->addDays(9)->toDateString();

    foreach (range(1, 5) as $n) {
        MerakiUser::create(['device_sn' => SN, 'pin' => (string) $n, 'name' => "Person {$n}", 'privilege' => 0]);
        punch((string) $n, "$day 08:00:00", 0);
        punch((string) $n, "$day 17:00:00", 1);
    }

    $html = loggedIn()->get('/meraki/report?pins[]=2&pins[]=4')->assertOk()->getContent();

    // Only the two picked, and both already open
    expect(substr_count($html, '<details class="one'))->toBe(2);
    expect(opened($html))->toBe(2);
    expect(worked($html))->toBe(['9 hours', '9 hours']);
    expect($html)->toContain('2 picked');
});

it('filters the overtime table and the punch list by employee', function () {
    $day = now()->startOfMonth()->addDays(9)->toDateString();

    foreach (range(1, 4) as $n) {
        MerakiUser::create(['device_sn' => SN, 'pin' => (string) $n, 'name' => "Person {$n}", 'privilege' => 0]);
        punch((string) $n, "$day 08:00:00", 0);
        punch((string) $n, "$day 17:00:00", 1);
    }

    $over = loggedIn()->get('/meraki/overtime?pins[]=1&pins[]=3')->assertOk()->getContent();

    expect(substr_count($over, '<td class="who">'))->toBe(2);

    $log = loggedIn()->get('/meraki/log?pins[]=1&pins[]=3')->assertOk()->getContent();

    expect(substr_count($log, '<td class="who">'))->toBe(4);   // two punches each
});

it('does not let a made up pin empty the page', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ahmed', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    punch('1', "$day 08:00:00", 0);
    punch('1', "$day 17:00:00", 1);

    // pin 77 belongs to nobody, so it is dropped and everyone is shown
    $html = loggedIn()->get('/meraki/report?pins[]=77')->assertOk()->getContent();

    expect($html)->toContain('Ahmed');
    expect(worked($html))->toBe(['9 hours']);
});

it('does not turn other days off when several employees are picked', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ahmed', 'privilege' => 0]);
    MerakiUser::create(['device_sn' => SN, 'pin' => '2', 'name' => 'Sara', 'privilege' => 0]);
    MerakiUser::create(['device_sn' => SN, 'pin' => '3', 'name' => 'Omar', 'privilege' => 0]);

    $d1 = now()->startOfMonth()->addDays(9)->toDateString();
    $d2 = now()->startOfMonth()->addDays(10)->toDateString();
    $d3 = now()->startOfMonth()->addDays(11)->toDateString();

    punch('1', "$d1 08:00:00", 0);
    punch('1', "$d1 17:00:00", 1);
    punch('2', "$d2 08:00:00", 0);
    punch('2', "$d2 17:00:00", 1);
    punch('3', "$d3 08:00:00", 0);
    punch('3', "$d3 17:00:00", 1);

    $picked = loggedIn()->get('/meraki/report?pins[]=1&pins[]=2')->getContent();

    // Omar's day is still a working day, even though he was not picked
    $gone = now()->day;

    expect(substr_count($picked, '<div class="day rest">'))->toBe($gone - 3);
});

// ---------------------------------------------------------- night shifts



it('leaves a day shift on the day it happened', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ahmed', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9);

    punch('1', $day->toDateString() . ' 08:00:00', 0);
    punch('1', $day->toDateString() . ' 17:00:00', 1);

    // The day before and the day after must both stay empty
    $before = loggedIn()->get('/meraki/report?from=' . $day->copy()->subDay()->toDateString()
        . '&to=' . $day->copy()->subDay()->toDateString())->getContent();

    expect(worked($before))->toBe([]);
    expect(worked(loggedIn()->get('/meraki/report?pins[]=1')->getContent()))->toBe(['9 hours']);
});

// ------------------------------------------------------- changing a shift

it('recounts a person the moment their shift changes', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Mover', 'privilege' => 0]);

    loggedIn()->post('/meraki/settings/shifts', ['shifts' => [
        ['id' => '1', 'name' => 'Day shift', 'start' => '08:00', 'end' => '17:00', 'grace' => 5],
        ['id' => '',  'name' => 'Half day',  'start' => '08:00', 'end' => '13:00', 'grace' => 5],
    ]]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    punch('1', "$day 08:00:00", 0);
    punch('1', "$day 14:00:00", 1);      // six hours

    // Nine hour day: three short, nothing extra
    $before = loggedIn()->get('/meraki/overtime')->getContent();

    expect($before)->toContain('Day shift');
    expect($before)->toContain('2 hours 55 min');   // 9h - 6h - 5 min grace

    loggedIn()->post('/meraki/settings/people', ['people' => ['1' => '2']]);

    // Five hour day: one hour extra, nothing short
    $after = loggedIn()->get('/meraki/overtime')->getContent();

    expect($after)->toContain('Half day');
    expect($after)->toContain('1 hour');
    expect(substr_count($after, 'class="n zero"'))->toBe(2);   // short and days-to-check
});

it('keeps everyone on their shift when the shifts are edited', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'A', 'privilege' => 0]);

    loggedIn()->post('/meraki/settings/shifts', ['shifts' => [
        ['id' => '1', 'name' => 'Day shift', 'start' => '08:00', 'end' => '17:00', 'grace' => 5],
        ['id' => '',  'name' => 'Half day',  'start' => '08:00', 'end' => '13:00', 'grace' => 0],
    ]]);

    loggedIn()->post('/meraki/settings/people', ['people' => ['1' => '2']]);

    loggedIn()->post('/meraki/settings/shifts', ['shifts' => [
        ['id' => '1', 'name' => 'Office hours', 'start' => '07:00', 'end' => '17:00', 'grace' => 5],
        ['id' => '2', 'name' => 'Short one',    'start' => '08:00', 'end' => '12:00', 'grace' => 0],
    ]]);

    expect(MerakiSettings::people('meraki'))->toBe(['1' => '2']);
    expect(MerakiSettings::shiftFor('meraki', '1')['name'])->toBe('Short one');
    expect(MerakiSettings::shiftFor('meraki', '1')['minutes'])->toBe(300);   // hours fixed
});

// --------------------------------------------------------- odd situations

it('can still filter to someone the device has not named yet', function () {
    $day = now()->startOfMonth()->addDays(9)->toDateString();

    punch('77', "$day 08:00:00", 0);   // no row in meraki_users at all
    punch('77', "$day 17:00:00", 1);

    $html = loggedIn()->get('/meraki/report')->assertOk()->getContent();

    expect($html)->toContain('PIN 77');
    expect($html)->toContain('value="77"');       // offered in the filter

    expect(worked(loggedIn()->get('/meraki/report?pins[]=77')->getContent()))->toBe(['9 hours']);
});

it('counts one employee once even if the url names them twice', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Twice', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    punch('1', "$day 08:00:00", 0);
    punch('1', "$day 17:00:00", 1);

    $html = loggedIn()->get('/meraki/report?pins[]=1&pins[]=1')->assertOk()->getContent();

    expect(substr_count($html, '<details class="one'))->toBe(1);
    expect($html)->toContain('>Twice<');          // not "2 picked"
});

it('gives the punch list exactly the range asked for, no padding', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Edge', 'privilege' => 0]);

    $from = now()->startOfMonth()->addDays(4);
    $to   = now()->startOfMonth()->addDays(6);

    punch('1', $from->toDateString() . ' 00:00:00', 0);
    punch('1', $to->toDateString() . ' 23:59:59', 1);
    punch('1', $from->copy()->subDay()->toDateString() . ' 09:00:00', 0);
    punch('1', $to->copy()->addDay()->toDateString() . ' 09:00:00', 0);

    $html = loggedIn()->get('/meraki/log?from=' . $from->toDateString() . '&to=' . $to->toDateString())
        ->assertOk()->getContent();

    // Both edges counted, neither neighbour
    expect($html)->toContain('<b>2</b>');
});

// ------------------------------------- the shift is stamped onto the punch

it('stamps the shift onto every punch the device sends', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ahmed', 'privilege' => 0]);

    loggedIn()->post('/meraki/settings/shifts', ['shifts' => [
        ['id' => '', 'name' => 'Half day', 'start' => '08:00', 'end' => '13:00', 'grace' => 0],
    ]]);

    loggedIn()->post('/meraki/settings/people', ['people' => ['1' => '2']]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    $this->call('POST', '/iclock/cdata?SN=' . SN . '&table=ATTLOG', [], [], [],
        ['CONTENT_TYPE' => 'text/plain'],
        "1\t$day 08:00:00\t0\t1\n1\t$day 16:00:00\t1\t1\n"
    )->assertOk();

    expect(Meraki::where('pin', '1')->pluck('shift_id')->all())->toBe(['2', '2']);
});

it('leaves a stamped day alone when the person is moved to another shift', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ahmed', 'privilege' => 0]);

    // Two shifts: 9 hours and 5 hours
    loggedIn()->post('/meraki/settings/shifts', ['shifts' => [
        ['id' => '', 'name' => 'Half day', 'start' => '08:00', 'end' => '13:00', 'grace' => 0],
    ]]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    // Punched while on the Day shift, and stamped as such
    Meraki::create(['device_sn' => SN, 'pin' => '1', 'punched_at' => "$day 08:00:00",
        'status' => 0, 'verify' => 1, 'raw' => 'test', 'shift_id' => '1']);
    Meraki::create(['device_sn' => SN, 'pin' => '1', 'punched_at' => "$day 16:00:00",
        'status' => 1, 'verify' => 1, 'raw' => 'test', 'shift_id' => '1']);

    $before = loggedIn()->get('/meraki/overtime')->getContent();

    expect($before)->toContain('Day shift');
    expect($before)->toContain('8 hours');            // worked
    expect($before)->toContain('55 min');             // short: 9h - 8h - 5 min grace

    // Move him to the five hour shift
    loggedIn()->post('/meraki/settings/people', ['people' => ['1' => '2']]);

    $after = loggedIn()->get('/meraki/overtime')->getContent();

    // That day was made under the Day shift and must not budge.
    // (Both shifts are named in the key at the bottom either way, so look
    //  at Ahmed's own row rather than the whole page.)
    preg_match('/<td class="who">Ahmed.*?<\/tr>/s', $after, $row);

    expect($row[0])->toContain('Day shift');
    expect($row[0])->not->toContain('Half day');
    expect($after)->toBe($before);
});

it('follows the new shift only for punches made after the move', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ahmed', 'privilege' => 0]);

    loggedIn()->post('/meraki/settings/shifts', ['shifts' => [
        ['id' => '', 'name' => 'Half day', 'start' => '08:00', 'end' => '13:00', 'grace' => 0],
    ]]);

    $d1 = now()->startOfMonth()->addDays(9)->toDateString();
    $d2 = now()->startOfMonth()->addDays(10)->toDateString();

    // Monday on the Day shift, Tuesday on the Half day — six hours each
    foreach ([[$d1, '1'], [$d2, '2']] as [$date, $shift]) {
        Meraki::create(['device_sn' => SN, 'pin' => '1', 'punched_at' => "$date 08:00:00",
            'status' => 0, 'verify' => 1, 'raw' => 'test', 'shift_id' => $shift]);
        Meraki::create(['device_sn' => SN, 'pin' => '1', 'punched_at' => "$date 14:00:00",
            'status' => 1, 'verify' => 1, 'raw' => 'test', 'shift_id' => $shift]);
    }

    $html = loggedIn()->get('/meraki/report?pins[]=1')->assertOk()->getContent();

    // Same six hours both days, but only the Half day one is overtime
    expect(worked($html))->toBe(['6 hours', '6 hours']);
    expect(extra($html))->toBe(['1 hour']);           // 6h on a 5h shift

    $over = loggedIn()->get('/meraki/overtime')->getContent();

    expect($over)->toContain('12 hours');             // worked, both days
    expect($over)->toContain('1 hour');               // extra, Tuesday only
    expect($over)->toContain('2 hours 55 min');       // short, Monday only
});

it('falls back to the current shift for punches with no stamp', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ahmed', 'privilege' => 0]);

    loggedIn()->post('/meraki/settings/shifts', ['shifts' => [
        ['id' => '', 'name' => 'Half day', 'start' => '08:00', 'end' => '13:00', 'grace' => 0],
    ]]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    // Recorded before the stamp existed
    punch('1', "$day 08:00:00", 0);
    punch('1', "$day 14:00:00", 1);

    expect(loggedIn()->get('/meraki/overtime')->getContent())->toContain('Day shift');

    loggedIn()->post('/meraki/settings/people', ['people' => ['1' => '2']]);

    // Nothing better to go on, so it follows him
    expect(loggedIn()->get('/meraki/overtime')->getContent())->toContain('Half day');
});

it('refuses to change the hours of a shift that is already saved', function () {
    loggedIn()->post('/meraki/settings/shifts', ['shifts' => [
        ['id' => '1', 'name' => 'Renamed', 'start' => '05:00', 'end' => '23:00', 'grace' => 199],
    ]])->assertRedirect('/meraki/settings?saved=1');

    $shift = MerakiSettings::shift('meraki', '1');

    expect($shift['name'])->toBe('Renamed');          // the name does move
    expect($shift['start'])->toBe('08:00');           // the hours do not
    expect($shift['end'])->toBe('17:00');
    expect($shift['grace'])->toBe(5);

    // and the page shows them as text, not as fields to type in
    $html = loggedIn()->get('/meraki/settings')->assertOk()->getContent();

    expect($html)->toContain('shifts[0][name]');
    expect($html)->not->toContain('shifts[0][start]');
    expect($html)->toContain('hours are fixed once saved');
});

it('names every shift a person worked under in the range', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Switcher', 'privilege' => 0]);

    loggedIn()->post('/meraki/settings/shifts', ['shifts' => [
        ['id' => '', 'name' => 'Half day', 'start' => '08:00', 'end' => '13:00', 'grace' => 0],
    ]]);

    $d1 = now()->startOfMonth()->addDays(9)->toDateString();
    $d2 = now()->startOfMonth()->addDays(10)->toDateString();

    foreach ([[$d1, '1'], [$d2, '2']] as [$date, $shift]) {
        Meraki::create(['device_sn' => SN, 'pin' => '1', 'punched_at' => "$date 08:00:00",
            'status' => 0, 'verify' => 1, 'raw' => 'test', 'shift_id' => $shift]);
        Meraki::create(['device_sn' => SN, 'pin' => '1', 'punched_at' => "$date 15:00:00",
            'status' => 1, 'verify' => 1, 'raw' => 'test', 'shift_id' => $shift]);
    }

    preg_match('/<td class="who">Switcher.*?<\/tr>/s',
        loggedIn()->get('/meraki/overtime')->getContent(), $row);

    expect($row[0])->toContain('Day shift, Half day');
});

it('uses the shift a day started on when the stamps differ mid day', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Split', 'privilege' => 0]);

    loggedIn()->post('/meraki/settings/shifts', ['shifts' => [
        ['id' => '', 'name' => 'Half day', 'start' => '08:00', 'end' => '13:00', 'grace' => 0],
    ]]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    Meraki::create(['device_sn' => SN, 'pin' => '1', 'punched_at' => "$day 08:00:00",
        'status' => 0, 'verify' => 1, 'raw' => 'test', 'shift_id' => '1']);
    Meraki::create(['device_sn' => SN, 'pin' => '1', 'punched_at' => "$day 14:00:00",
        'status' => 1, 'verify' => 1, 'raw' => 'test', 'shift_id' => '2']);

    preg_match('/<td class="who">Split.*?<\/tr>/s',
        loggedIn()->get('/meraki/overtime')->getContent(), $row);

    expect($row[0])->toContain('Day shift');
    expect($row[0])->not->toContain('Half day');
});

it('falls back safely when a punch names a shift that is gone', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ghosted', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    Meraki::create(['device_sn' => SN, 'pin' => '1', 'punched_at' => "$day 08:00:00",
        'status' => 0, 'verify' => 1, 'raw' => 'test', 'shift_id' => '77']);
    Meraki::create(['device_sn' => SN, 'pin' => '1', 'punched_at' => "$day 17:00:00",
        'status' => 1, 'verify' => 1, 'raw' => 'test', 'shift_id' => '77']);

    $html = loggedIn()->get('/meraki/overtime')->assertOk()->getContent();

    expect($html)->toContain('Day shift');      // the first shift, not a crash
    expect($html)->toContain('9 hours');
});

it('does not re-stamp a punch the device sends twice', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Repeat', 'privilege' => 0]);

    loggedIn()->post('/meraki/settings/shifts', ['shifts' => [
        ['id' => '', 'name' => 'Half day', 'start' => '08:00', 'end' => '13:00', 'grace' => 0],
    ]]);

    $day  = now()->startOfMonth()->addDays(9)->toDateString();
    $body = "1\t$day 08:00:00\t0\t1\n";
    $url  = '/iclock/cdata?SN=' . SN . '&table=ATTLOG';

    $this->call('POST', $url, [], [], [], ['CONTENT_TYPE' => 'text/plain'], $body);

    loggedIn()->post('/meraki/settings/people', ['people' => ['1' => '2']]);

    // The device re-sends it after a reconnect, now that he has moved
    $this->call('POST', $url, [], [], [], ['CONTENT_TYPE' => 'text/plain'], $body);

    expect(Meraki::count())->toBe(1);
    expect(Meraki::first()->shift_id)->toBe('1');   // still the shift of the day
});
