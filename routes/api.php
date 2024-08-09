<?php

use App\Http\Controllers\LabScheduleController;
use App\Http\Controllers\NfcTagController;
use App\Http\Controllers\UserController;

Route::apiResource('posts', UserController::class);
Route::get('users/{email}', [UserController::class, 'getUserByEmail']);
Route::apiResource('nfc-tags', NfcTagController::class);
Route::apiResource('lab-schedules', LabScheduleController::class);