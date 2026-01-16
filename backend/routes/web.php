<?php

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

/** Fix: storage link not working in cpanel */
Route::get('/media/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath)) {
        abort(404);
    }
    // $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    // $mime = mime_content_type($fullPath);
    // if (!in_array($mime, $allowedMimeTypes)) {
    //     abort(403, 'Unauthorized file type');
    // }
    return response()->file($fullPath);
})->where('path', '.*');

Route::get('/', function () {
    if (\Auth::guard('admin')->check()) {
        return redirect('/admin/dashboard');
    }
    return redirect('/login');
});

use App\Http\Controllers\PaymentController;


Route::post('/payment/success', [PaymentController::class, 'success'])
    ->name('payment.success')
    ->withoutMiddleware(['auth', 'verified', 'csrf']);
Route::post('/payment/fail', [PaymentController::class, 'fail'])->name('payment.fail');
Route::post('/payment/cancel', [PaymentController::class, 'cancel'])->name('payment.cancel');

Route::get('/payment/citybank/callback', [PaymentController::class, 'citybankCallback'])->name('payment.citybank.callback');


use App\Http\Controllers\AdminAuthController;

Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
Route::post('/admin/login', [AdminAuthController::class, 'bladeLogin']);


use App\Http\Controllers\AdminDashboardController;

Route::get('/admin/dashboard', [AdminDashboardController::class, 'bladeIndex']);

// Admin Event Booking Form (Blade)
use App\Http\Controllers\HallsController;
Route::get('/admin/bookings/create', [HallsController::class, 'adminBookingForm']);
Route::post('/admin/bookings/block', [HallsController::class, 'blockBooking']);

// Admin Hall Management Page
Route::get('/admin/halls', function () {
    return view('admin.halls');
});

// API Endpoints for Hall Management
Route::prefix('api/halls')->group(function () {
    Route::get('/', [HallsController::class, 'index']);
    Route::post('/', [HallsController::class, 'store']);
    Route::get('{id}', [HallsController::class, 'show']);
    Route::put('{id}/basic', [HallsController::class, 'updateBasic']);
    Route::delete('{id}', [HallsController::class, 'destroy']);

    // Charges
    Route::post('{id}/charge', [HallsController::class, 'addCharge']);
    Route::put('{id}/charge', [HallsController::class, 'updateCharge']);
    Route::delete('{id}/charge', [HallsController::class, 'deleteCharge']);

    // Policy Content
    Route::post('{id}/policy-content', [HallsController::class, 'addPolicyContent']);
    Route::put('{id}/policy-content', [HallsController::class, 'updatePolicyContent']);
    Route::delete('{id}/policy-content', [HallsController::class, 'deletePolicyContent']);
    Route::post('{id}/policy-content/search', [HallsController::class, 'searchPolicyContent']);

    // Images
    Route::post('{id}/images', [HallsController::class, 'addImages']);
    Route::delete('{id}/images', [HallsController::class, 'deleteImage']);

    // Policy PDF
    Route::post('{id}/policy-pdf', [HallsController::class, 'addPolicyPdf']);
    Route::delete('{id}/policy-pdf', [HallsController::class, 'deletePolicyPdf']);
});


// Admin bookings management page (Blade)
use App\Http\Controllers\BookingController;
Route::match(['get', 'post'], '/admin/bookings', [BookingController::class, 'adminBookingPage'])->name('admin.bookings');

// Admins management page (Blade)
use App\Http\Controllers\AdminController;
Route::get('/admin/admins', [AdminController::class, 'index'])->name('admin.admins');
Route::post('/admin/admins', [AdminController::class, 'store'])->name('admin.admins.store');
Route::get('/admin/admins/{id}/edit', [AdminController::class, 'edit'])->name('admin.admins.edit');
Route::put('/admin/admins/{id}', [AdminController::class, 'update'])->name('admin.admins.update');
Route::delete('/admin/admins/{id}', [AdminController::class, 'destroy'])->name('admin.admins.destroy');

// Admin members management page (Blade)
use App\Http\Controllers\MemberController;
Route::get('/admin/members', [MemberController::class, 'index'])->name('admin.members');
Route::post('/admin/members', [MemberController::class, 'store'])->name('admin.members.store');
Route::get('/admin/members/{id}/edit', [MemberController::class, 'edit'])->name('admin.members.edit');
Route::put('/admin/members/{id}', [MemberController::class, 'update'])->name('admin.members.update');
Route::delete('/admin/members/{id}', [MemberController::class, 'destroy'])->name('admin.members.destroy');
// CSV Import for Members
Route::post('/admin/members/import-csv', [MemberController::class, 'importCsv'])->name('admin.members.import.csv');
Route::post('/admin/members/import-csv-map', [MemberController::class, 'importCsvMap'])->name('admin.members.import.csv.map');

// Admin logout route for navbar
Route::post('/admin/logout', [AdminAuthController::class, 'bladeLogout'])->name('admin.logout');

Route::get('/admin/bookings/report', [\App\Http\Controllers\BookingController::class, 'downloadReport'])->name('admin.bookings.report');

use App\Http\Controllers\LogController;

Route::get('/admin/logs', [LogController::class, 'index'])->name('admin.logs');

use App\Http\Controllers\AdminPaymentController;

Route::prefix('admin')->group(function () {
    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('admin.payments');
    Route::get('/payments/report', [AdminPaymentController::class, 'downloadReport'])->name('admin.payments.report');
    Route::get('/payments/{payment}/invoice', [AdminPaymentController::class, 'downloadInvoice'])->name('admin.payments.invoice');
});

Route::get('/nagad/callback', [PaymentController::class, 'nagadCallback']);