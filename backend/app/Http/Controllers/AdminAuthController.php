<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Use Laravel Auth facade
use Illuminate\Validation\ValidationException;
use App\Models\Admin;
use App\Helpers\SimpleLogger;

class AdminAuthController extends Controller
{
    // Show the login form view
    public function showLoginForm()
    {
        return view('admin.login');
    }

    // Handle the login form POST
    public function bladeLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required'],
            'password' => ['required'],
        ]);

        // Attempt login with the default guard (no guard specified)
        if (Auth::guard('admin')->attempt($credentials)) {
            $request->session()->regenerate(); // Prevent session fixation
            $admin = Auth::guard('admin')->user();
            SimpleLogger::log('bladeLogin', $admin->name . ' has logged in');
            return redirect()->intended('/admin/dashboard');
        }

        return back()->withErrors([
            'email' => 'Invalid credentials.',
        ])->withInput($request->only('email'));
    }

    // Logout the admin
    public function bladeLogout(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        if ($admin) {
            SimpleLogger::log('bladeLogout', $admin->name . ' has logged out');
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
