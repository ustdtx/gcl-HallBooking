<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HallsController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminDashboardController;

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
Route::post('/halls/{id}/policy-pdf/add', [HallsController::class, 'addPolicyPdf']);
Route::post('/halls/{id}/policy-pdf/update', [HallsController::class, 'updatePolicyPdf']);
Route::patch('/halls/{id}/policy-pdf/delete', [HallsController::class, 'deletePolicyPdf']);
Route::post('/halls/{id}/images', [HallsController::class, 'addImages']);
Route::delete('/halls/{id}/images', [HallsController::class, 'deleteImage']);
Route::patch('/halls/{id}/policy-content/add', [HallsController::class, 'addPolicyContent']);
Route::patch('/halls/{id}/policy-content/update', [HallsController::class, 'updatePolicyContent']);
Route::patch('/halls/{id}/policy-content/delete', [HallsController::class, 'deletePolicyContent']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/bookings', [BookingController::class, 'store']); // create booking
    Route::get('/bookings/user', [BookingController::class, 'userBookings']); // user's all bookings
    // hall bookings by month
    Route::put('/bookings/{id}/cancel', [BookingController::class, 'cancel']); // cancel booking
// Route for updating booking
    Route::put('/bookings/{id}', [BookingController::class, 'update']);
    Route::post('bookings/request-cancel', [BookingController::class, 'setToReview']);
});
Route::middleware('auth:sanctum')->group(function () {
    // Admin-only API routes (backend will check role)
    
    Route::post('/bookings/block', [BookingController::class, 'adminBlock']);
    Route::get('/bookings/unavailable', [BookingController::class, 'unavailableBookings']);
    
    // Search and report (allowed for all authenticated)
    
    Route::match(['get', 'post'], '/bookings/search', [\App\Http\Controllers\BookingController::class, 'searchBookings']);
});
Route::patch('/admin/bookings/{id}/status', [\App\Http\Controllers\BookingController::class, 'adminUpdateStatus']);
Route::post('/payments/manual-add', [PaymentController::class, 'manualAdd']);

Route::get('/bookings/hall/{hall_id}', [BookingController::class, 'hallBookings']); 
Route::post('/calculate-charge', [BookingController::class, 'calculateCharge']);
Route::get('/bookings/{id}', [BookingController::class, 'show']);

Route::get('/bookings', [BookingController::class, 'allBookings']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/payment/initiate', [PaymentController::class, 'initiate']);

});
Route::get('/payments', [PaymentController::class, 'index']);
Route::get('/payments/by-booking', [PaymentController::class, 'paymentsByBooking']);

Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::middleware('auth:sanctum')->get('/me', [AdminAuthController::class, 'me']);
    Route::middleware('auth:sanctum')->post('/logout', [AdminAuthController::class, 'logout']);
});

Route::middleware('auth:sanctum')->get('/admin/dashboard', [AdminDashboardController::class, 'index']);


Route::post('/bookings/report', [\App\Http\Controllers\BookingController::class, 'generateReport']);

Route::post('/payments/nagad', [PaymentController::class, 'nagadPay']);