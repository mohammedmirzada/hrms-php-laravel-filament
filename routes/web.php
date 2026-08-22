<?php

use Illuminate\Support\Facades\Route;

Route::match(['get', 'post'], '/iclock/cdata', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Log::info('ZK cdata', [
        'method' => $request->method(),
        'query'  => $request->query(),
        'body'   => $request->getContent(),
    ]);
    return response('OK');
});

Route::match(['get', 'post'], '/iclock/getrequest', function () {
    return response('OK');
});