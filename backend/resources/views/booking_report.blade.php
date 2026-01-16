<!DOCTYPE html>
<html>
<head>
    <title>Gulshan Club Limited</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 4px; text-align: left; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h1 style="text-align:center; margin-bottom:0.5em; text-transform:uppercase; letter-spacing:2px;">Gulshan Club Limited</h1>
    <h2>Booking Report</h2>
    <div>
        <b>Total Bookings:</b> {{ $total }}<br>
        <b>By Hall:</b>
        @foreach($byHall as $hall => $count)
            Hall #{{ $hall }}: {{ $count }}&nbsp;
        @endforeach
        <br>
        <b>By Shift:</b>
        @foreach($byShift as $shift => $count)
            {{ $shift }}: {{ $count }}&nbsp;
        @endforeach
        <br>
        <b>By Status:</b>
        @foreach($byStatus as $status => $count)
            {{ $status }}: {{ $count }}&nbsp;
        @endforeach
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Hall</th>
                <th>Date</th>
                <th>Shift</th>
                <th>Status</th>
                <th>Booked On</th>
                <th>Club Account</th>
            </tr>
        </thead>
        <tbody>
        @foreach($bookings as $b)
            <tr>
                <td>{{ $b->id }}</td>
                <td>{{ $b->hall->name ?? $b->hall_id }}</td>
                <td>{{ $b->booking_date }}</td>
                <td>{{ $b->shift }}</td>
                <td>{{ $b->status }}</td>
                <td>{{ $b->created_at ? $b->created_at->format('Y-m-d') : '' }}</td>
                <td>{{ $b->member->club_account ?? $b->club_account ?? 'N/A' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
