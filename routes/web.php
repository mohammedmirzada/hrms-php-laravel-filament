<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

Route::match(['get','post'], '/iclock/cdata', function (\Illuminate\Http\Request $request) {
    $sn = $request->query('SN');

    // Registration/handshake (GET, or POST with table=options)
    if ($request->isMethod('get') || $request->query('table') === 'options') {
        \Log::info('ZK handshake', ['SN' => $sn, 'q' => $request->query()]);
        $body = "GET OPTION FROM: {$sn}\r\n"
              . "ServerVersion=3.0.1\r\n"
              . "ServerName=ADMS\r\n"
              . "PushVersion=3.0.1\r\n"
              . "ErrorDelay=60\r\n"
              . "RequestDelay=2\r\n"
              . "TransTimes=00:00;14:00\r\n"
              . "TransInterval=1\r\n"
              . "TransTables=User Transaction\r\n"
              . "Realtime=1\r\n"
              . "TimeoutSec=10\r\n";
        return response($body, 200)->header('Content-Type', 'text/plain');
    }

    // Attendance data (POST with table=ATTLOG etc.)
    \Log::info('ZK data', ['SN' => $sn, 'table' => $request->query('table'), 'body' => $request->getContent()]);
    return response('OK', 200)->header('Content-Type', 'text/plain');
});

Route::match(['get','post'], '/iclock/getrequest', function (\Illuminate\Http\Request $request) {
    \Log::info('ZK getrequest', ['SN' => $request->query('SN')]);
    return response('OK', 200)->header('Content-Type', 'text/plain');
});