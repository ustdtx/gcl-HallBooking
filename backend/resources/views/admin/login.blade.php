@extends('layouts.app')

@section('title', 'Admin Login')

@section('content')
<div class="d-flex justify-content-center align-items-center vh-100 bg-light">
    <form method="POST" action="/admin/login" class="bg-white p-4 p-md-5 rounded shadow w-100" style="max-width: 400px;">
        @csrf

        <div class="text-center mb-4">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 64px; height: 64px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <h2 class="fw-bold">Admin Login</h2>
            <p class="text-muted">Sign in to your admin account</p>
        </div>

        @if(session('error'))
            <div class="alert alert-danger text-center">{{ session('error') }}</div>
        @endif

        <div class="mb-3">
            <input type="text" name="email" class="form-control" placeholder="Email or Username" value="{{ old('email') }}" required autofocus>
        </div>
        <div class="mb-4">
            <input type="password" name="password" class="form-control" placeholder="Password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 fw-semibold">Login</button>
    </form>
</div>
@endsection
