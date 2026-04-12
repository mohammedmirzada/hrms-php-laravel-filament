<?php

use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes (no authentication required)
|--------------------------------------------------------------------------
*/

Route::prefix('hikvision')->group(function () {

    Route::post('/events', [EventController::class, 'eventData']);

});
