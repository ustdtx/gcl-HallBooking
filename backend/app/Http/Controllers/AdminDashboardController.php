<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Hall;
use App\Models\Member;
use App\Models\Payment;

class AdminDashboardController extends Controller
{
    public function bladeIndex()
    {
        // Require admin authentication
        if (!\Auth::guard('admin')->check()) {
            return redirect('/login');
        }

        $data = [
            'total_users' => Member::count(),
            'total_bookings' => Booking::count(),
            'total_halls' => Hall::count(),
            'total_revenue' => Payment::where('status', 'Success')->sum('amount'),
        ];

        $sessionData = session()->all();
        return view('admin.dashboard', compact('data', 'sessionData'));
    }
}
 