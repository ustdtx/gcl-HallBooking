@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Add Member</h2>
    <div class="card mb-4">
        <div class="card-body">
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
@endsection
