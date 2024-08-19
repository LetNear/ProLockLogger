<?php

use App\Http\Controllers\LabScheduleController;
use App\Http\Controllers\NfcTagController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserInformationController;

Route::apiResource('user', UserController::class);
Route::get('users/{email}', [UserController::class, 'getUserByEmail']);
Route::post('userInfo', [UserInformationController::class, 'getUserDetails']);
Route::get('getuserbyfingerprint/{fingerprint_id}', [UserController::class, 'getUserByFingerprint']);
Route::get('users/role/{role_id}', [UserController::class, 'getUsersByRole']);
Route::put('/users/update-fingerprint', [UserController::class, 'updateFingerprintByEmail']);
Route::get('users', [UserController::class, 'index']);
Route::post('users', [UserController::class, 'store']);
Route::get('users/{id}', [UserController::class, 'show']);
Route::put('users/{id}', [UserController::class, 'update']);
Route::delete('users/{id}', [UserController::class, 'destroy']);
Route::apiResource('nfc-tags', NfcTagController::class);
Route::apiResource('lab-schedules', LabScheduleController::class);