<?php

use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\Home;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SeatController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('auth.login');
});

// Authentication Routes
Route::get('auth/google', [GoogleAuthController::class, 'redirect'])->name('google.auth');
Route::get('auth/google/call-back', [GoogleAuthController::class, 'callbackGoogle']);

// Login Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

// Home Routes
Route::middleware(['auth'])->group(function () {
    Route::get('home', [Home::class, 'index'])->name('home');
    Route::post('/comlabcreateattendance', [Home::class, 'create_attendance'])->name('comlab.create.attendance');
});

Route::post('/admin/logout', [LoginController::class, 'logout'])->name('filament.admin.auth.logout');

Route::get('/notepad', function () {
    return "<a href='notepad://'> Notepade </a>";
});

Route::post('/assign-seat', [SeatController::class, 'assignSeat'])->name('assign.seat');

// Route for removing a student from a seat
Route::post('/remove-student', [SeatController::class, 'removeStudent'])->name('remove.student');