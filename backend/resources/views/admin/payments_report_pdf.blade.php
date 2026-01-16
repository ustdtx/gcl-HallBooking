<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h2 { color: #1a237e; text-align: center; font-size: 20px; }
        h4 { color: #3949ab; margin-bottom: 20px; font-size: 16px; }
        table { width: 100%; border-collapse: collapse; background: #e8eaf6; font-size: 11px; }
        th, td { border: 1px solid #b0bec5; padding: 4px; text-align: left; }
        th { background: #3949ab; color: #fff; }
        .summary { margin-bottom: 15px; }
    </style>
</head>
<body>
    <h2>Gulshan Club Limited</h2>
    <h4 style="text-align:left;">Payment Report</h4>
    <div class="summary">
        <strong>Total Bookings:</strong> {{ $totalBookings }}<br>
        <strong>Total Revenue:</strong> {{ number_format($totalRevenue, 2) }}<br>
        <strong>Pre-Book Amount:</strong> {{ number_format($preBookAmount, 2) }}<br>
        <strong>Final Payment Amount:</strong> {{ number_format($finalPaymentAmount, 2) }}<br>
        <strong>Revenue by Hall:</strong>
        <ul>
        @foreach($hallRevenue as $hall => $amount)
            <li>{{ $hall }}: {{ number_format($amount, 2) }}</li>
        @endforeach
        </ul>
    </div>
    <table>
        <thead>
            <tr>
                <th>Booking ID</th>
                <th>Club Account</th>
                <th>Hall Name</th>
                <th>Date</th>
                <th>Shift</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Tran ID</th>
                <th>Created On</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
            <tr>
                <td>{{ $payment->booking_id }}</td>
                <td>{{ $payment->booking->member->club_account ?? '' }}</td>
                <td>{{ $payment->booking->hall->name ?? '' }}</td>
                <td>{{ $payment->booking->booking_date ?? '' }}</td>
                <td>{{ $payment->booking->shift ?? '' }}</td>
                <td>{{ ucfirst($payment->type) }}</td>
                <td>{{ number_format($payment->amount, 2) }}</td>
                <td>{{ $payment->tran_id }}</td>
                <td>{{ $payment->created_at->format('Y-m-d H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
