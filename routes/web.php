<?php

use App\Livewire\SubmitLeaveResuest;
use Filament\Http\Middleware\SetUpPanel;
use Illuminate\Support\Facades\Route;

// Leave Request Routes
Route::get('/submit/leave-requests', SubmitLeaveResuest::class)->name('submit-leave-request');