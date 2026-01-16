<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Invoice</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; color: #1a237e; }
        .section { margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; background: #e8eaf6; }
        th, td { border: 1px solid #b0bec5; padding: 6px; text-align: left; }
        th { background: #3949ab; color: #fff; }
    </style>
</head>
<body>
    <h2 class="header">Gulshan Club Limited</h2>
    <h4 style="color:#3949ab;">Payment Invoice</h4>
    <div class="section">
        <strong>Booking ID:</strong> {{ $payment->booking_id }}<br>
        <strong>Club Account:</strong> {{ $payment->booking->member->club_account ?? '' }}<br>
        <strong>Name:</strong> {{ $payment->booking->member->name ?? '' }}<br>
        <strong>Hall Name:</strong> {{ $payment->booking->hall->name ?? '' }}<br>
        <strong>Date:</strong> {{ $payment->booking->booking_date ?? '' }}<br>
        <strong>Shift:</strong> {{ $payment->booking->shift ?? '' }}<br>
        <strong>Type:</strong> {{ ucfirst($payment->type) }}<br>
        <strong>Amount:</strong> {{ number_format($payment->amount, 2) }}<br>
        <strong>Tran ID:</strong> {{ $payment->tran_id }}<br>
        <strong>Created On:</strong> {{ $payment->created_at->format('Y-m-d H:i') }}<br>
    </div>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ ucfirst($payment->type) }} Payment for Booking #{{ $payment->booking_id }}</td>
                <td>{{ number_format($payment->amount, 2) }}</td>
            </tr>
        </tbody>
    </table>
    <div class="section" style="margin-top:30px;">
        <em>Thank you for your payment.</em>
    </div>
</body>
</html>
