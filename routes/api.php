<?php

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ScheduleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/schedules/today', [ScheduleController::class, 'today']);
    Route::put('/schedules/{schedule}/check', [ScheduleController::class, 'check']);
    Route::put('/schedules/{schedule}/actual', [ScheduleController::class, 'actual']);
    Route::apiResource('schedules', ScheduleController::class);
    Route::apiResource('projects', ProjectController::class);
});
