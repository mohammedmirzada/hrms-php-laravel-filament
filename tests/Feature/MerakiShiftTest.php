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

    loggedIn()->post('/meraki/settings', ['shift_start' => '9:00', 'shift_end' => '15:00'])
        ->assertRedirect('/meraki/settings?saved=1');

    expect(MerakiSettings::shift('meraki'))->toBe(['start' => '09:00', 'end' => '15:00']);
    expect(MerakiSettings::shiftMinutes('meraki'))->toBe(360);

    $html = loggedIn()->get('/meraki/report?pin=5')->assertOk()->getContent();

    expect(extra($html))->toBe(['2 hours']);   // 8h worked, 6h work day
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
    $page->assertSee('3 hours');         // Ahmed extra
    $page->assertSee('30 min');          // Sara extra
    $page->assertSee('2 hours');         // Sara short
    $page->assertSee('21 hours');        // Ahmed worked 12h + 9h
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
    loggedIn()->post('/meraki/settings', ['shift_start' => '08:00', 'shift_end' => '08:00'])
        ->assertOk()
        ->assertSee('cannot be the same time');

    expect(MerakiSettings::shiftMinutes('meraki'))->toBe(540);   // still 9 hours
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

    // Greyed, with no label written in the box
    $inMonth = $day->daysInMonth;

    expect(substr_count($html, '<div class="day rest">'))->toBe($inMonth - 1);
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
    $inMonth = now()->daysInMonth;

    expect(substr_count($everyone, '<div class="day rest">'))->toBe($inMonth - 2);
    expect(substr_count($justSara, '<div class="day rest">'))->toBe($inMonth - 2);
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

it('keeps the month and person filters while paging', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '1', 'name' => 'Ahmed', 'privilege' => 0]);
    MerakiUser::create(['device_sn' => SN, 'pin' => '2', 'name' => 'Sara', 'privilege' => 0]);

    $day   = now()->startOfMonth()->addDays(9)->toDateString();
    $month = now()->format('Y-m');

    foreach (range(0, 119) as $i) {
        punch('1', sprintf('%s %02d:%02d:00', $day, 8 + intdiv($i, 60), $i % 60), $i % 2);
    }

    punch('2', "$day 08:00:00", 0);   // Sara, filtered out

    $html = loggedIn()->get("/meraki/log?month={$month}&pin=1")->assertOk()->getContent();

    expect($html)->toContain('pin=1');
    expect($html)->toContain('month=' . urlencode($month));
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

it('writes hours and minutes in words', function () {
    expect(MerakiSettings::readable(0))->toBe('0 min');
    expect(MerakiSettings::readable(45))->toBe('45 min');
    expect(MerakiSettings::readable(60))->toBe('1 hour');
    expect(MerakiSettings::readable(65))->toBe('1 hour 5 min');
    expect(MerakiSettings::readable(243))->toBe('4 hours 3 min');
    expect(MerakiSettings::readable(480))->toBe('8 hours');
});

it('handles a night shift length', function () {
    loggedIn()->post('/meraki/settings', ['shift_start' => '22:00', 'shift_end' => '06:00']);

    expect(MerakiSettings::shiftMinutes('meraki'))->toBe(480);
});

it('rejects a rubbish shift time', function () {
    loggedIn()->post('/meraki/settings', ['shift_start' => 'banana', 'shift_end' => '17:00'])
        ->assertOk()
        ->assertSee('Could not save');

    expect(MerakiSettings::shift('meraki')['start'])->toBe('08:00');
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
