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
        $data = json_decode($request->input('AccessControllerEvent'), true);

        // Ignore heartbeats and non-attendance events
        if (!$data || $data['eventType'] !== 'AccessControllerEvent') {
            return response('OK', 200);
        }

        Log::info('Hikvision Attendance Event', $data);

        return response('OK', 200);
    });

});
