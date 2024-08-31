<?php

use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\LabScheduleController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\NfcTagController;
use App\Http\Controllers\SeatController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RedirectIfAuthenticated;

// Root Route
Route::get('/', function () {
    return view('auth.login');
})->middleware(RedirectIfAuthenticated::class);

// Authentication Routes
Route::get('auth/google', [GoogleAuthController::class, 'redirect'])->name('google.auth')
    ->middleware(['guest', RedirectIfAuthenticated::class]); // Apply both guest and custom middleware

Route::get('auth/google/call-back', [GoogleAuthController::class, 'callbackGoogle'])
    ->name('google.callback')
    ->middleware(['guest', RedirectIfAuthenticated::class]); // Apply both guest and custom middleware

// Login Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')
    ->middleware(['guest', RedirectIfAuthenticated::class]); // Apply both guest and custom middleware

Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

// Logout Route
Route::post('/admin/logout', [LoginController::class, 'logout'])->name('filament.admin.auth.logout');

// Seat Assignment Routes
Route::post('/assign-seat', [SeatController::class, 'assignSeat'])->name('assign.seat');
Route::post('/remove-student', [SeatController::class, 'removeStudent'])->name('remove.student');


Route::get('prolock://', [NfcTagController::class, 'show'])
    ->name('nfc.details');

Route::get('/schedule', [LabScheduleController::class, 'showSchedule']);
