<?php

use App\Http\Controllers\Api\BuzzerController;
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

// Buzzer webhook endpoint
Route::post('/buzzer', [BuzzerController::class, 'store'])->name('api.buzzer');
Route::post('/buzzer/test', [BuzzerController::class, 'test'])->name('api.buzzer.test');
Route::post('/buzzer/status', [BuzzerController::class, 'status'])->name('api.buzzer.status');
Route::post('/buzzer/reset', [BuzzerController::class, 'reset'])->name('api.buzzer.reset');