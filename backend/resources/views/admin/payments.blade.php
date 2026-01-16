@extends('layouts.app')

@section('content')
<div class="container">

    <h2 class="text-center" style="color:#1a237e;">Gulshan Club Limited</h2>
    <h4 class="mt-2 mb-4" style="color:#3949ab;">Payment Report</h4>
    <div class="mb-3">
        <form method="GET" action="{{ route('admin.payments') }}" class="">
            <div class="row g-2 mb-2">
                <div class="col">
                    <input type="text" name="booking_id" placeholder="Booking ID" value="{{ request('booking_id') }}" class="form-control">
                </div>
                <div class="col">
                    <input type="text" name="club_account" placeholder="Club Account" value="{{ request('club_account') }}" class="form-control">
                </div>
                <div class="col">
                    <input type="text" name="tran_id" placeholder="Transaction ID" value="{{ request('tran_id') }}" class="form-control">
                </div>
            </div>
            <div class="row g-2 mb-2 align-items-end">
                <div class="col">
                    <label for="date_from" class="form-label mb-0">From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}" class="form-control">
                </div>
                <div class="col">
                    <label for="date_to" class="form-label mb-0">To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}" class="form-control">
                </div>
                <div class="col">
                    <label for="per_page" class="form-label mb-0">Entries</label>
                    <select id="per_page" name="per_page" class="form-select">
                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                        <option value="150" {{ request('per_page') == 150 ? 'selected' : '' }}>150</option>
                        <option value="200" {{ request('per_page') == 200 ? 'selected' : '' }}>200</option>
                        <option value="all" {{ request('per_page') == 'all' ? 'selected' : '' }}>All</option>
                    </select>
                </div>
                <div class="col">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
                <div class="col">
                    <a href="{{ route('admin.payments.report', request()->all()) }}" class="btn btn-success w-100">Download PDF Report</a>
                </div>
            </div>
        </form>
    </div>
    <div class="mb-3">
        <strong>Total Payments:</strong> {{ $totalPayments }} |
        <strong>Total Revenue:</strong> {{ number_format($totalRevenue) }} |
        <strong>Pre-Book Amount:</strong> {{ number_format($preBookAmount) }} |
        <strong>Final Payment Amount:</strong> {{ number_format($finalPaymentAmount) }}
        <br>
        <strong>Revenue by Hall:</strong>
        @foreach($hallRevenue as $hall => $amount)
            {{ $hall }}: {{ number_format($amount) }} |
        @endforeach
        <br>

    </div>
    <table class="table table-striped" style="background:#e8eaf6;">
        <thead>
            <tr>
                <th>Booking ID</th>
                <th>Club Account</th>
                <th>Name</th>
                <th>Hall Name</th>
                <th>Date</th>
                <th>Shift</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Tran ID</th>
                <th>Created On</th>
                <th>Invoice</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
            <tr>
                <td>{{ $payment->booking_id }}</td>
                <td>{{ $payment->booking->member->club_account ?? '' }}</td>
                <td>{{ $payment->booking->member->name ?? '' }}</td>
                <td>{{ $payment->booking->hall->name ?? '' }}</td>
                <td>{{ $payment->booking->booking_date ?? '' }}</td>
                <td>{{ $payment->booking->shift ?? '' }}</td>
                <td>{{ ucfirst($payment->type) }}</td>
                <td>{{ number_format($payment->amount, 2) }}</td>
                <td>{{ $payment->tran_id }}</td>
                <td>{{ $payment->created_at->format('Y-m-d H:i') }}</td>
                <td>
                    <a href="{{ route('admin.payments.invoice', $payment->id) }}" class="btn btn-sm btn-info">Download Invoice</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{ $payments->withQueryString()->links('pagination::bootstrap-4') }}
</div>
@endsection
