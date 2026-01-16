@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Edit Admin</h2>
    <div class="card mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.admins.update', $editAdmin->id) }}">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-2 mb-3">
                        <input type="text" class="form-control" name="name" value="{{ old('name', $editAdmin->name) }}" placeholder="Name" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <input type="email" class="form-control" name="email" value="{{ old('email', $editAdmin->email) }}" placeholder="Email" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <input type="password" class="form-control" name="password" placeholder="New Password (leave blank to keep current)">
                    </div>
                    <div class="col-md-2 mb-3">
                        <input type="role" class="form-control" name="role" value="{{ old('role', $editAdmin->role) }}" placeholder="Role (optional)">
                    </div>
                    <div class="col-md-2 mb-3">
                        <input type="text" class="form-control" name="phone" value="{{ old('phone', $editAdmin->phone) }}" placeholder="Phone (optional)">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('admin.admins') }}" class="btn btn-secondary">Cancel</a>
            </form>
            @if($errors->any())
                <div class="alert alert-danger mt-3">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
