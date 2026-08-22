<?php

use App\Http\Controllers\Miraki\MirakiDeviceController;
use App\Http\Controllers\Miraki\MirakiReportController;
use App\Http\Middleware\ReportAuth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Fingerprint devices — ZKTeco ADMS / PUSH
|--------------------------------------------------------------------------
|
| Shared by every client. The device cannot be pointed at another path, so
| requests are matched to a client by serial number (config/miraki.php).
|
| These routes run with NO web middleware on purpose: sessions live in the
| database, and the device polls every 30 seconds, so the web group would
| create thousands of junk session rows a day.
|
*/

Route::prefix('iclock')->group(function () {

    Route::match(['get', 'post'], 'cdata', [MirakiDeviceController::class, 'cdata']);
    Route::match(['get', 'post'], 'getrequest', [MirakiDeviceController::class, 'getrequest']);
    Route::match(['get', 'post'], 'devicecmd', [MirakiDeviceController::class, 'devicecmd']);

});

/*
|--------------------------------------------------------------------------
| Attendance reports — one set of URLs per client
|--------------------------------------------------------------------------
|
|   /miraki/report        the report
|   /miraki/pull-users    refresh the pin -> name list from the device
|
| A new client in config/miraki.php gets the same URLs under its own slug.
|
*/

// Built from the config keys, so a new client needs no route change.
// Falls back to a generic slug pattern when the config cache is stale —
// without this, a cached config from before config/miraki.php existed would
// crash every artisan command, including config:clear itself.
$slugs = array_keys((array) config('miraki.clients'));

$slugPattern = $slugs
    ? implode('|', array_map('preg_quote', $slugs))
    : '[a-z0-9-]+';

Route::middleware(ReportAuth::class)
    ->prefix('{client}')
    ->where(['client' => $slugPattern])
    ->group(function () {

        Route::get('report', [MirakiReportController::class, 'index'])
            ->name('client.report');

        Route::get('pull-users', [MirakiDeviceController::class, 'pullUsers'])
            ->name('client.pull-users');

    });
