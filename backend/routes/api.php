<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HallsController;

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

// Authentication routes
Route::post('/auth/request-otp', [AuthController::class, 'requestOtp']);
Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);

// Hall management routes
Route::get('/halls', [HallsController::class, 'index']);
Route::get('/halls/{id}', [HallsController::class, 'show']);
Route::post('/halls', [HallsController::class, 'store']);
Route::put('/halls/{id}', [HallsController::class, 'update']);
Route::delete('/halls/{id}', [HallsController::class, 'destroy']);
Route::patch('/halls/{id}/basic', [HallsController::class, 'updateBasic']);
Route::patch('/halls/{id}/charges/add', [HallsController::class, 'addCharge']);
Route::patch('/halls/{id}/charges/update', [HallsController::class, 'updateCharge']);
Route::patch('/halls/{id}/charges/delete', [HallsController::class, 'deleteCharge']);
