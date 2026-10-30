<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::prefix('auth')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);

    Route::middleware('guest')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
        Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:6,1');
        Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:6,1');
        Route::get('/google/redirect', [GoogleController::class, 'redirect']);
        Route::get('/google/callback', [GoogleController::class, 'callback']);
    });

    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
});

Route::get('/', function () {
    return view('index');
});

// Catch-all route for Vue Router history mode
Route::get('/{any}', function () {
    return view('index');
})->where('any', '.*');

