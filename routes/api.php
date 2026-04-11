<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Public API Routes (no authentication required)
|--------------------------------------------------------------------------
*/

Route::prefix('hikvision')->group(function () {

    Route::post('/events', function (Request $request) {
        Log::info('Hikvision Event Received', [
            'headers' => $request->headers->all(),
            'body' => $request->getContent(),
            'all' => $request->all(),
            'raw' => file_get_contents('php://input'),
        ]);
        
        return response('OK', 200);
    });

});
