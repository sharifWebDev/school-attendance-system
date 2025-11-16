<?php

use App\Http\Controllers\API\V1\AttendanceController;
use App\Http\Controllers\Api\V1\Admin\AuthController;
use App\Http\Controllers\Api\V1\StudentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;



// path1

// Route::post('/admin/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->get('/v1/user', function (Request $request) {
    return '$request->user()';
});

Route::middleware('auth:sanctum', 'verified')
    // ->prefix('/v1')
    ->group(function () {
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);

    // Student routes
    Route::apiResource('students', StudentController::class);
    Route::get('/students/classes', [StudentController::class, 'getClasses']);
    Route::get('/students/sections', [StudentController::class, 'getSections']);

    // Attendance routes
    Route::prefix('attendance')->group(function () {
        Route::post('/bulk', [AttendanceController::class, 'recordBulk']);
        Route::get('/monthly-report', [AttendanceController::class, 'getMonthlyReport']);
        Route::get('/today-summary', [AttendanceController::class, 'getTodaySummary']);
        Route::get('/class-summary', [AttendanceController::class, 'getClassSummary']);
        Route::get('/', [AttendanceController::class, 'index']);
        Route::get('/{attendance}', [AttendanceController::class, 'show']);
        Route::delete('/{attendance}', [AttendanceController::class, 'destroy']);
    });

    });

// Public routes (if any)
Route::post('/login', [\App\Http\Controllers\API\AuthController::class, 'login']);
