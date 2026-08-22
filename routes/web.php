<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ZKTeco ADMS / PUSH  —  iFace950 Plus (SN SFAA254900353)
|--------------------------------------------------------------------------
| The device only talks to these fixed paths, it cannot use /api/*.
| Every reply must be text/plain, or the device retries forever.
*/

Route::match(['get', 'post'], '/iclock/cdata', function (Request $request) {

    $sn    = $request->query('SN');
    $table = $request->query('table');

    // 1. Handshake: device asks the server for its config (GET)
    if ($request->isMethod('get')) {

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

    // 3. Real data: ATTLOG (punches), OPERLOG, USERINFO...
    $body = $request->getContent();

    // Only punches are interesting. OPERLOG / USERINFO / etc. are ignored.
    if ($table === 'ATTLOG') {

        Log::info('ZK punch', ['SN' => $sn, 'body' => $body]);

        // TODO: parse ATTLOG lines -> push to Google Sheet
        //       one punch per line, tab separated:
        //       PIN \t YYYY-MM-DD HH:MM:SS \t status \t verify \t workcode \t ...
    }

    // Device expects the number of rows we accepted
    $rows = count(array_filter(explode("\n", trim($body))));

    return response("OK: {$rows}", 200)->header('Content-Type', 'text/plain');
});

Route::match(['get', 'post'], '/iclock/getrequest', function (Request $request) {

    // No commands queued for the device. Not logged — it polls constantly.
    return response('OK', 200)->header('Content-Type', 'text/plain');
});
