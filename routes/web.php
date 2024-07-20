<?php

use App\Http\Controllers\Home;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('home', [Home::class, 'index']);
Route::post('/comlabcreateattendance', [Home::class, 'create_attendance'])->name('comlabcreateattendance');