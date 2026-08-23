<?php

use App\Models\Meraki;
use App\Models\MerakiUser;
use App\Support\MerakiSettings;
/*
 * Runs against the real dev DB but under a fake serial number, so nothing
 * real is touched. Everything is deleted again at the end.
 */

const SN = 'TEST-SN-0001';

$savedSettings = null;

beforeEach(function () use (&$savedSettings) {
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

    $html = loggedIn()->get('/meraki/report')->assertOk()->getContent();

    expect($html)->toContain('Ahmed');
    expect(worked($html))->toBe(['8 hours']);   // 9h on site minus 1h lunch
    expect(extra($html))->toBe([]);             // 8h worked, 9h work day
});

it('counts overtime as worked hours over the shift length', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '2', 'name' => 'Sara', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    punch('2', "$day 07:00:00", 0);
    punch('2', "$day 19:00:00", 1);   // 12h worked, shift is 9h

    $html = loggedIn()->get('/meraki/report')->assertOk()->getContent();

    expect(worked($html))->toBe(['12 hours']);
    expect(extra($html))->toBe(['3 hours']);
});

it('marks a day where the check out is missing', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '3', 'name' => 'Ali', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    punch('3', "$day 08:00:00", 0);   // in, never out

    $html = loggedIn()->get('/meraki/report')->assertOk()->getContent();

    expect($html)->toContain('Missing a check out');
    expect($html)->toContain('not yet');       // no out time to show
    expect(worked($html))->toBe(['0 min']);
});

it('says no check in when the day starts with an OUT', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '7', 'name' => 'Hussein', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    punch('7', "$day 17:00:00", 1);   // out, with no in before it

    $html = loggedIn()->get('/meraki/report')->assertOk()->getContent();

    expect($html)->toContain('Missing a check in');
});

it('does not cry about a check out pressed twice', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '8', 'name' => 'Kareem', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    punch('8', "$day 08:00:00", 0);   // in
    punch('8', "$day 17:00:00", 1);   // out
    punch('8', "$day 17:00:03", 1);   // finger read again, 3 seconds later

    $html = loggedIn()->get('/meraki/report')->assertOk()->getContent();

    expect(worked($html))->toBe(['9 hours']);
    expect($html)->not->toContain('Missing');   // nothing is actually missing
});

it('ignores a repeated IN instead of turning it into an OUT', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '4', 'name' => 'Zaid', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    punch('4', "$day 08:00:00", 0);   // in
    punch('4', "$day 08:05:00", 0);   // in again by mistake
    punch('4', "$day 17:00:00", 1);   // out

    $html = loggedIn()->get('/meraki/report')->assertOk()->getContent();

    expect(worked($html))->toBe(['9 hours']);   // 08:00 -> 17:00, not 08:05
});

it('saves a new shift from the settings page and uses it', function () {
    MerakiUser::create(['device_sn' => SN, 'pin' => '5', 'name' => 'Omar', 'privilege' => 0]);

    $day = now()->startOfMonth()->addDays(9)->toDateString();

    punch('5', "$day 08:00:00", 0);
    punch('5', "$day 16:00:00", 1);   // 8h worked

    expect(extra(loggedIn()->get('/meraki/report')->getContent()))->toBe([]);

    loggedIn()->post('/meraki/settings', ['shift_start' => '9:00', 'shift_end' => '15:00'])
        ->assertRedirect('/meraki/settings?saved=1');

    expect(MerakiSettings::shift('meraki'))->toBe(['start' => '09:00', 'end' => '15:00']);
    expect(MerakiSettings::shiftMinutes('meraki'))->toBe(360);

    $html = loggedIn()->get('/meraki/report')->assertOk()->getContent();

    expect(extra($html))->toBe(['2 hours']);   // 8h worked, 6h work day
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
