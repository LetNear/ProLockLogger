<?php

use App\Http\Controllers\FingerprintController;
use App\Http\Controllers\LabScheduleController;
use App\Http\Controllers\NfcTagController;
use App\Http\Controllers\OpenAndCloseLogController;
use App\Http\Controllers\RecentLogsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserInformationController;
use App\Http\Controllers\StudentAttendanceController;

Route::apiResource('user', UserController::class);
Route::get('users/{email}', [UserController::class, 'getUserByEmail']);
Route::get('userInfo/{email}', [UserInformationController::class, 'getUserDetailsViaEmail']);
Route::put('/userInfo/update', [UserInformationController::class, 'updateUserDetails']);


Route::get('getuserbyfingerprint/{fingerprint_id}', [UserController::class, 'getUsersByFingerprint']);
Route::get('users/role/{role_id}', [UserController::class, 'getUsersByRole']);
Route::get('admin/role/{role_id}', [UserController::class, 'getUsersByRole1']);



Route::put('/users/update-fingerprint', [UserController::class, 'updateFingerprintByEmail']);
Route::get('users', [UserController::class, 'index']);
Route::post('users', [UserController::class, 'store']);
Route::get('users/{id}', [UserController::class, 'show']);
Route::put('users/{id}', [UserController::class, 'update']);
Route::delete('users/{id}', [UserController::class, 'destroy']);
Route::apiResource('nfc-tags', NfcTagController::class);


Route::get('/fingerprints', [FingerprintController::class, 'index']);
Route::post('/fingerprints', [FingerprintController::class, 'store']);

Route::get('/userinformation', [UserInformationController::class, 'index']);
Route::put('/user-information/id-card', [UserInformationController::class, 'updateIdCardId']);
Route::get('/userinformation/id-card', [UserInformationController::class, 'getIdCardId']);
Route::get('/user-information/by-id-card', [UserInformationController::class, 'getUserInformationByIdCardId']); 
Route::put('/user-information/update-id-card', [UserInformationController::class, 'updateIdCardIdByUserNumber']);
Route::get('/user-information/{user_number}', [UserInformationController::class, 'getUserInformationByUserNumber']);



Route::get('/recent-logs', [RecentLogsController::class, 'index']);
Route::put('/logs/time-in', [RecentLogsController::class, 'createRecordTimeInByUID']);
Route::put('/logs/time-out', [RecentLogsController::class, 'createRecordTimeOutByUID']);
Route::put('/logs/time-in/fingerprint', [RecentLogsController::class, 'createRecordTimeInByFingerprintId']);
Route::put('/logs/time-out/fingerprint', [RecentLogsController::class, 'createRecordTimeOutByFingerprintId']);
Route::get('/recent-logs/by-uid', [RecentLogsController::class, 'getRecentLogsByUID']);
Route::get('/recent-logs/by-fingerid', [RecentLogsController::class, 'getRecentLogsByFingerprintId']);


Route::get('/lab-schedules/faculty/{fingerprint_id}', [LabScheduleController::class, 'getFacultyScheduleByFingerprintId']);
Route::get('/lab-schedules/email/{email}', [LabScheduleController::class, 'getFacultyScheduleByEmail']);


Route::get('/student-count/{email}', [UserInformationController::class, 'getStudentCountByInstructorEmail']);
Route::get('/instructor/schedule-count/{email}', [LabScheduleController::class, 'getInstructorScheduleCountByEmail']);
Route::get('instructor/next-schedule/{email}', [LabScheduleController::class, 'getNextScheduleTimeByEmail']);
Route::get('/student-schedule-count', [LabScheduleController::class, 'getStudentScheduleCountByEmail']);
Route::get('/total-logs-count', [RecentLogsController::class, 'getTotalLogsCountByEmail']);


Route::get('/lab-schedules/fingerprint/{fingerprint_id}', [LabScheduleController::class, 'getLabScheduleDataByFingerprintId']);
Route::get('/student-schedule/{email}', [LabScheduleController::class, 'getStudentScheduleByEmail']);
Route::get('/current-date-time', [UserController::class, 'getCurrentDateTime']);
Route::get('/student/lab-schedule/rfid/{rfid_number}', [LabScheduleController::class, 'getLabScheduleOfStudentByRFID']);
Route::get('/lab-schedules', [LabScheduleController::class, 'getAllLabSchedules']);
Route::post('/enroll-student', [LabScheduleController::class, 'enrollStudentToCourse']);
Route::get('/enrolled-courses/{email}', [LabScheduleController::class, 'getEnrolledCoursesByEmail']);
Route::post('/door/open', [OpenAndCloseLogController::class, 'openDoor']);
Route::post('/door/close', [OpenAndCloseLogController::class, 'closeDoor']);
Route::get('/logs', [OpenAndCloseLogController::class, 'getAllLogs']);


Route::get('/attendance/instructor', [StudentAttendanceController::class, 'getStudentAttendanceByInstructor']);
Route::get('/courses/details', [LabScheduleController::class, 'getCourseDetailsByEmail']);
Route::post('/courses/update', [LabScheduleController::class, 'updateCourseDetails']);
Route::get('/student/schedule-details', [LabScheduleController::class, 'getStudentScheduleDetailsByEmail']);


Route::get('/check-enrolled-courses', [LabScheduleController::class, 'checkEnrolledCourses']);

Route::post('/door/log-status', [OpenAndCloseLogController::class, 'logDoorStatus']);

// Route::post('/recent-logs/time-in', [RecentLogsController::class, 'createRecordTimeInByUID']);
// Route::put('/recent-logs/update-time-out', [RecentLogsController::class, 'updateLogsByUIDForTimeOut']);
// Route::post('/recent-logs/create-log', [RecentLogsController::class, 'createLogsByUID']);

// // Route::post('/recent-logs/time-in', [RecentLogsController::class, 'createRecordTimeInByUID']);
// Route::put('/recent-logs/update-time-out', [RecentLogsController::class, 'updateLogsByUIDForTimeOut']);
// Route::post('/recent-logs/create-log', [RecentLogsController::class, 'createLogsByUID']);

