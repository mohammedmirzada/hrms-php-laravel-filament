<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

// Handshake — device registers here on boot. MUST return config, not just OK.
Route::get('/iclock/cdata', function (Request $request) {
    $sn = $request->query('SN');
    Log::info('ZK handshake', ['SN' => $sn]);

    $body = "GET OPTION FROM: {$sn}\r\n"
          . "ATTLOGStamp=None\r\n"
          . "OPERLOGStamp=None\r\n"
          . "ATTPHOTOStamp=None\r\n"
          . "ErrorDelay=30\r\n"
          . "Delay=10\r\n"
          . "TransTimes=00:00;14:05\r\n"
          . "TransInterval=1\r\n"
          . "TransFlag=1111000000\r\n"
          . "TimeZone=3\r\n"
          . "Realtime=1\r\n"
          . "Encrypt=0\r\n";

    return response($body, 200)->header('Content-Type', 'text/plain');
});

// Attendance/event data lands here.
Route::post('/iclock/cdata', function (Request $request) {
    Log::info('ZK data', ['body' => $request->getContent()]);
    return response('OK', 200)->header('Content-Type', 'text/plain');
});

// Device polls here for commands.
Route::get('/iclock/getrequest', function (Request $request) {
    Log::info('ZK getrequest', ['SN' => $request->query('SN')]);
    return response('OK', 200)->header('Content-Type', 'text/plain');
});