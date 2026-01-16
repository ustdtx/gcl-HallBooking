@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Members</h1>
            @php
                $admin = Auth::guard('admin')->user();
            @endphp  
    @if($admin && $admin->role === 'admin')
    <div class="mb-3">
        <form action="{{ route('admin.members.import.csv') }}" method="POST" enctype="multipart/form-data" class="d-inline-block">
            @csrf
            <label for="csv_file" class="form-label">Import Members (CSV):</label>
            <input type="file" name="csv_file" id="csv_file" accept=".csv" class="form-control d-inline-block w-auto" required>
            <button type="submit" class="btn btn-success">Upload CSV</button>
        </form>
    </div>
    @endif
    <div class="card mb-4">
        <div class="card-body">
            @php
                $admin = Auth::guard('admin')->user();
            @endphp
            @if($admin && $admin->role === 'admin')
            <form method="POST" action="{{ route('admin.members.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-2 mb-3">
                        <input type="text" class="form-control" name="name" placeholder="Name" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <input type="text" class="form-control" name="club_account" placeholder="Club Account" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <input type="email" class="form-control" name="email" placeholder="Email" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <input type="text" class="form-control" name="phone" placeholder="Phone" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <input type="text" class="form-control" name="address" placeholder="Address" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <input type="date" class="form-control" name="date_joined" placeholder="Date Joined (optional)">
                        <small class="text-muted">Leave blank for today</small>
                    </div>
                    <div class="col-md-1 mb-3">
                        <button type="submit" class="btn btn-primary">Add</button>
                    </div>
                </div>
            </form>
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
    <form method="GET" action="{{ route('admin.members') }}" class="mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label>Name</label>
                <input type="text" name="name" value="{{ $filters['name'] }}" class="form-control" placeholder="Search by name">
            </div>
            <div class="col-md-3">
                <label>Email</label>
                <input type="text" name="email" value="{{ $filters['email'] }}" class="form-control" placeholder="Search by email">
            </div>
            <div class="col-md-3">
                <label>Club Account</label>
                <input type="text" name="club_account" value="{{ $filters['club_account'] }}" class="form-control" placeholder="Search by club account">
            </div>
            <div class="col-md-2">
                <label>Per Page</label>
                <select name="per_page" class="form-select" onchange="this.form.submit()">
                    @foreach($perPageOptions as $option)
                        <option value="{{ $option }}" @if($filters['per_page'] == $option) selected @endif>{{ $option == 'all' ? 'All' : $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Club Account</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Date Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $member)
                    <tr>
                        <td>{{ $member->id }}</td>
                        <td>{{ $member->name }}</td>
                        <td>{{ $member->club_account }}</td>
                        <td>{{ $member->email }}</td>
                        <td>{{ $member->phone }}</td>
                        <td>{{ $member->address }}</td>
                        <td>{{ $member->created_at ? $member->created_at->format('Y-m-d') : '' }}</td>
                        <td>
                            @if($admin && $admin->role === 'admin')
                                <a href="{{ route('admin.members.edit', $member->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                <form action="{{ route('admin.members.destroy', $member->id) }}" method="POST" style="display:inline-block" onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center">No members found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($filters['per_page'] !== 'all' && method_exists($members, 'links'))
        <div class="d-flex justify-content-center">
            {{ $members->appends(request()->except('page'))->links() }}
        </div>
    @endif
</div>
@endsection
