<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\MirakiEventController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes (no authentication required)
|--------------------------------------------------------------------------
*/

Route::prefix('hikvision')->group(function () {

    Route::post('/events', [EventController::class, 'eventData']);

});

Route::prefix('miraki')->group(function () {

    Route::post('/events', [MirakiEventController::class, 'eventData']);

});
