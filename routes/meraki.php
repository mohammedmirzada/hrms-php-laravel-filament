<?php

use App\Http\Controllers\Meraki\MerakiAuthController;
use App\Http\Controllers\Meraki\MerakiDeviceController;
use App\Http\Controllers\Meraki\MerakiReportController;
use App\Http\Controllers\Meraki\MerakiSettingsController;
use App\Http\Middleware\ReportAuth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Fingerprint devices — ZKTeco ADMS / PUSH
|--------------------------------------------------------------------------
|
| Shared by every client. The device cannot be pointed at another path, so
| requests are matched to a client by serial number (config/meraki.php).
|
| These routes run with NO web middleware on purpose: sessions live in the
| database, and the device polls every 30 seconds, so the web group would
| create thousands of junk session rows a day.
|
*/

Route::prefix('iclock')->group(function () {

    Route::match(['get', 'post'], 'cdata', [MerakiDeviceController::class, 'cdata']);
    Route::match(['get', 'post'], 'getrequest', [MerakiDeviceController::class, 'getrequest']);
    Route::match(['get', 'post'], 'devicecmd', [MerakiDeviceController::class, 'devicecmd']);

});

/*
|--------------------------------------------------------------------------
| Attendance pages — one set of URLs per client
|--------------------------------------------------------------------------
|
|   /meraki/login     login page
|   /meraki/report    calendar, the main page
|   /meraki/log       plain list of every punch
|   /meraki/settings  shift start and end time
|
| A new client in config/meraki.php gets the same URLs under its own slug.
|
| These DO use the web group: people look at them, not devices, so the
| session cost is nothing and the login page needs a session and CSRF.
|
*/

// The old spelling was /miraki/... — send those to the right place instead
// of a 404, for anyone who bookmarked it.
Route::get('miraki/{rest?}', fn (string $rest = 'report') => redirect('/meraki/' . $rest))
    ->where('rest', '.*');

// Built from the config keys, so a new client needs no route change.
// Falls back to a generic slug pattern when the config cache is stale —
// without this, a cached config from before config/meraki.php existed would
// crash every artisan command, including config:clear itself.
$slugs = array_keys((array) config('meraki.clients'));

$slugPattern = $slugs
    ? implode('|', array_map('preg_quote', $slugs))
    : '[a-z0-9-]+';

Route::middleware('web')
    ->prefix('{client}')
    ->where(['client' => $slugPattern])
    ->group(function () {

        Route::get('login', [MerakiAuthController::class, 'showLogin'])
            ->name('client.login');

        Route::post('login', [MerakiAuthController::class, 'login'])
            ->middleware('throttle:10,1');       // slows password guessing

        Route::post('logout', [MerakiAuthController::class, 'logout'])
            ->name('client.logout');

        Route::middleware(ReportAuth::class)->group(function () {

            Route::get('report', [MerakiReportController::class, 'calendar'])
                ->name('client.report');

            Route::get('log', [MerakiReportController::class, 'log'])
                ->name('client.log');

            Route::get('settings', [MerakiSettingsController::class, 'edit'])
                ->name('client.settings');

            Route::post('settings', [MerakiSettingsController::class, 'update']);

        });

    });
