<?php
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SeatController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RedirectIfAuthenticated;

// Root Route
Route::get('/', function () {
    return view('auth.login');
})->middleware(RedirectIfAuthenticated::class);  // Apply RedirectIfAuthenticated middleware

// Authentication Routes
Route::get('auth/google', [GoogleAuthController::class, 'redirect'])->name('google.auth');
Route::get('auth/google/call-back', [GoogleAuthController::class, 'callbackGoogle']);

// Login Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')
    ->middleware(['guest', RedirectIfAuthenticated::class]);  // Apply guest and RedirectIfAuthenticated middleware

Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

// Logout Route
Route::post('/admin/logout', [LoginController::class, 'logout'])->name('filament.admin.auth.logout');

// Seat Assignment Routes
Route::post('/assign-seat', [SeatController::class, 'assignSeat'])->name('assign.seat');
Route::post('/remove-student', [SeatController::class, 'removeStudent'])->name('remove.student');
