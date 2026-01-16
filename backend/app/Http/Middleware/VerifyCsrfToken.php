<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'payment/success',
        'payment/fail',
        'payment/cancel',
        'api/admin/bookings/*/status', // Exclude admin status update API from CSRF
        'api/calculate-charge', // Exclude calculate-charge API from CSRF
        'api/payments/manual-add', // Exclude manual payment add API from CSRF

        // Exclude all hall management API endpoints from CSRF
        'api/halls',
        'api/halls/*',
        // Exclude all booking API endpoints from CSRF
        'api/bookings',
        'api/bookings/*',
        // Exclude all policy-content endpoints
        'api/halls/*/policy-content/*',
        // Exclude all policy-pdf endpoints
        'api/halls/*/policy-pdf/*',
        // Exclude all images endpoints
        'api/halls/*/images',
        // Exclude all charges endpoints
        'api/halls/*/charges/*',
    ];
}
