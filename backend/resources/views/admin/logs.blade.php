@extends('layouts.app')

@section('title', 'Admin Action Logs')

@section('content')
<div class="container mt-4">
    <h2>Admin Action Logs</h2>
    <form method="GET" class="mb-3">
        <div class="input-group" style="max-width:400px;">
            <input type="text" name="admin_name" class="form-control" placeholder="Search by admin name" value="{{ request('admin_name') }}">
            <button class="btn btn-primary" type="submit">Search</button>
        </div>
    </form>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Time</th>
                <th>Name</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at }}</td>
                    <td>{{ $log->name }}</td>
                    <td>{{ $log->description }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No logs found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div>
        {{ $logs->appends(request()->query())->links('pagination::bootstrap-4') }}
    </div>
</div>
@endsection
