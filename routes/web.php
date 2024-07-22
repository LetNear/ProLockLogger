<?php

use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\Home;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
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
