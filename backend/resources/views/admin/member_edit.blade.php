@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Edit Member</h2>
    <div class="card mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.members.update', $editMember->id) }}">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-2 mb-3">
                        <input type="text" class="form-control" name="name" value="{{ old('name', $editMember->name) }}" placeholder="Name" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <input type="text" class="form-control" name="club_account" value="{{ old('club_account', $editMember->club_account) }}" placeholder="Club Account" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <input type="email" class="form-control" name="email" value="{{ old('email', $editMember->email) }}" placeholder="Email" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <input type="text" class="form-control" name="phone" value="{{ old('phone', $editMember->phone) }}" placeholder="Phone" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <input type="text" class="form-control" name="address" value="{{ old('address', $editMember->address) }}" placeholder="Address" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <input type="date" class="form-control" name="date_joined" value="{{ old('date_joined', $editMember->date_joined) }}" placeholder="Date Joined (optional)">
                        <small class="text-muted">Leave blank to keep current or use today if not set</small>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('admin.members') }}" class="btn btn-secondary">Cancel</a>
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
