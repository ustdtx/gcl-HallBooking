


@extends('layouts.app')

@section('content')
@if(Auth::guard('admin')->check() && Auth::guard('admin')->user()->role === 'admin')
<div class="container mt-4">
    <h2>Admins</h2>
    <div class="card mb-4">
        <div class="card-body">
            <form method="POST" action="/admin/admins">
                @csrf
                <div class="row">
                    <div class="col-md-2 mb-3">
                        <input type="text" class="form-control" name="name" placeholder="Name" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <input type="email" class="form-control" name="email" placeholder="Email" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <input type="password" class="form-control" name="password" placeholder="Password" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <input type="role" class="form-control" name="role" placeholder="Role (optional)">
                    </div>
                    <div class="col-md-2 mb-3">
                        <input type="text" class="form-control" name="phone" placeholder="Phone (optional)">
                    </div>
                    <div class="col-md-2 mb-3">
                        <button type="submit" class="btn btn-primary">Add</button>
                    </div>
                </div>
            </form>
            <hr>
            <form method="GET" action="{{ route('admin.admins') }}" class="mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label>Per Page</label>
                        <select name="per_page" class="form-select" onchange="this.form.submit()">
                            @foreach($perPageOptions as $option)
                                <option value="{{ $option }}" @if($perPage == $option) selected @endif>{{ $option == 'all' ? 'All' : $option }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($admins as $admin)
                            <tr>
                                <td>{{ $admin->id }}</td>
                                <td>{{ $admin->name }}</td>
                                <td>{{ $admin->email }}</td>
                                <td>{{ $admin->role }}</td>
                                <td>{{ $admin->phone }}</td>
                                <td>
                                    <a href="{{ route('admin.admins.edit', $admin->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                    <form action="{{ route('admin.admins.destroy', $admin->id) }}" method="POST" style="display:inline-block" onsubmit="return confirm('Are you sure?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">No admins found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($perPage !== 'all' && method_exists($admins, 'links'))
                <div class="d-flex justify-content-center">
                    {{ $admins->appends(request()->except('page'))->links() }}
                </div>
            @endif
            @if(session('success'))
                <div class="alert alert-success mt-3">{{ session('success') }}</div>
            @endif
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
@else
<div class="container mt-5">
    <div class="alert alert-danger text-center">
        You do not have access to this page.<br>
        <a href="/admin/dashboard" class="btn btn-link">Go Back</a>
    </div>
</div>
@endif
@endsection

