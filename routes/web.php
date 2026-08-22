<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ZKTeco ADMS / PUSH  —  iFace950 Plus (SN SFAA254900353)
|--------------------------------------------------------------------------
| The device only talks to these fixed paths, it cannot use /api/*.
| Every reply must be text/plain, or the device retries forever.
|
| Punches (ATTLOG) carry only the PIN, never the name.
| Names live in the USERINFO table, which the device sends when we ask.
*/

Route::match(['get', 'post'], '/iclock/cdata', function (Request $request) {

    $sn    = $request->query('SN');
    $table = $request->query('table');

    // 1. Handshake: device asks the server for its config (GET)
    if ($request->isMethod('get')) {

        // Device just booted — queue a "send me your users" command for it
        Cache::store('file')->put("zk:{$sn}:pull_users", true, now()->addMinutes(10));

        $body = "GET OPTION FROM: {$sn}\r\n"
              . "Stamp=9999\r\n"          // data marker — without it the device re-registers
              . "OpStamp=9999\r\n"
              . "ErrorDelay=30\r\n"
              . "Delay=30\r\n"            // seconds between getrequest polls
              . "RequestDelay=30\r\n"
              . "TransTimes=00:00;14:00\r\n"
              . "TransInterval=1\r\n"
              . "TransFlag=1111000000\r\n"
              . "TransTables=User Transaction\r\n"
              . "Realtime=1\r\n"          // push punches the moment they happen
              . "Encrypt=0\r\n";

        return response($body, 200)->header('Content-Type', 'text/plain');
    }

    // 2. Device is uploading its OWN options — just acknowledge.
    //    Sending the config block here made it loop every second.
    if ($table === 'options') {
        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    $body = $request->getContent();

    // 3a. Punches
    if ($table === 'ATTLOG') {

        Log::info('ZK punch', ['SN' => $sn, 'body' => $body]);

        // TODO: parse ATTLOG lines -> push to Google Sheet
        //       one punch per line, tab separated:
        //       PIN \t YYYY-MM-DD HH:MM:SS \t status \t verify \t workcode \t ...
    }

    // 3b. User list — this is where the names come from
    if ($table === 'USERINFO' || $table === 'OPERLOG') {

        // OPERLOG also carries "USER PIN=..." lines when a user is added on the device
        if (str_contains($body, 'USER PIN=')) {
            Log::info('ZK user', ['SN' => $sn, 'body' => $body]);

            // TODO: parse "USER PIN=1<TAB>Name=Ahmad<TAB>..." -> PIN => Name map
        }
    }

    // Anything else (OPERLOG noise, BIODATA, photos...) is dropped silently.

    // Device expects the number of rows we accepted
    $rows = count(array_filter(explode("\n", trim($body))));

    return response("OK: {$rows}", 200)->header('Content-Type', 'text/plain');
});

Route::match(['get', 'post'], '/iclock/getrequest', function (Request $request) {

    $sn = $request->query('SN');

    // One-shot after each boot: make the device upload its whole user list.
    // pull() reads and clears, so it is only sent once.
    if (Cache::store('file')->pull("zk:{$sn}:pull_users")) {
        return response("C:1:DATA QUERY USERINFO\r\n", 200)
            ->header('Content-Type', 'text/plain');
    }

    // Nothing queued. Not logged — the device polls constantly.
    return response('OK', 200)->header('Content-Type', 'text/plain');
});

// Device reports back the result of a command we sent (C:1:...)
Route::match(['get', 'post'], '/iclock/devicecmd', function (Request $request) {

    return response('OK', 200)->header('Content-Type', 'text/plain');
});

// Manual trigger — open this in a browser to ask the device for its user list
// without rebooting it. The device picks it up on its next poll (max 30s).
Route::get('/iclock/pull-users', function () {

    Cache::store('file')->put('zk:SFAA254900353:pull_users', true, now()->addMinutes(10));

    return 'Queued. Watch the log for "ZK user" within 30 seconds.';
});
