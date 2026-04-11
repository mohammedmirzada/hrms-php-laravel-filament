<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Request;

/*
|--------------------------------------------------------------------------
| Public API Routes (no authentication required)
|--------------------------------------------------------------------------
*/

Route::prefix('hikvision')->group(function () {

    Route::get('/events', function (Request $request) {
        Log::info('Hikvision Event Received', [
            'headers' => $request->headers->all(),
            'body' => $request->getContent(),
        ]);
        return response('OK', 200);
    });

});
