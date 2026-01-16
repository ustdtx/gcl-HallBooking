@extends('layouts.app')

@section('title', 'Admin Booking Management')
@section('content')
@php $admin = Auth::guard('admin')->user(); @endphp
<div class="container">
    <h2 class="text-center" style="color:#1a237e;">Gulshan Club Limited</h2>
    <h4 class="mt-2 mb-4" style="color:#3949ab;">Booking Management</h4>
    @if($admin)
        <div class="alert alert-info mb-3">Logged in as: <b>{{ $admin->name }}</b> (Role: <b>{{ $admin->role }}</b>)</div>
    @endif
    <form method="GET" action="{{ route('admin.bookings') }}" class="mb-3">
        <div class="row g-2 mb-2">
            <div class="col">
                <input type="text" name="booking_id" placeholder="Booking ID" value="{{ old('booking_id', $filters['booking_id'] ?? '') }}" class="form-control">
            </div>
            <div class="col">
                <input type="text" name="club_account" placeholder="Club Account" value="{{ old('club_account', $filters['club_account'] ?? '') }}" class="form-control">
            </div>
            <div class="col">
                <input type="text" name="email" placeholder="Email" value="{{ old('email', $filters['email'] ?? '') }}" class="form-control">
            </div>
            <div class="col">
                <select name="hall_id" class="form-select">
                    <option value="">All Halls</option>
                    @foreach($halls as $hall)
                        <option value="{{ $hall->id }}" {{ (old('hall_id', $filters['hall_id'] ?? '') == $hall->id) ? 'selected' : '' }}>{{ $hall->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col">
                <select name="shift" class="form-select">
                    <option value="">All Shifts</option>
                    <option value="FN" {{ (old('shift', $filters['shift'] ?? '') == 'FN') ? 'selected' : '' }}>FN</option>
                    <option value="AN" {{ (old('shift', $filters['shift'] ?? '') == 'AN') ? 'selected' : '' }}>AN</option>
                    <option value="FD" {{ (old('shift', $filters['shift'] ?? '') == 'FD') ? 'selected' : '' }}>FD</option>
                </select>
            </div>
            <div class="col">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="Unpaid" {{ (old('status', $filters['status'] ?? '') == 'Unpaid') ? 'selected' : '' }}>Unpaid</option>
                    <option value="Pre-Booked" {{ (old('status', $filters['status'] ?? '') == 'Pre-Booked') ? 'selected' : '' }}>Pre-Booked</option>
                    <option value="Booked" {{ (old('status', $filters['status'] ?? '') == 'Booked') ? 'selected' : '' }}>Booked</option>
                    <option value="Cancelled" {{ (old('status', $filters['status'] ?? '') == 'Cancelled') ? 'selected' : '' }}>Cancelled</option>
                    <option value="Unavailable" {{ (old('status', $filters['status'] ?? '') == 'Unavailable') ? 'selected' : '' }}>Unavailable</option>
                    <option value="Review" {{ (old('status', $filters['status'] ?? '') == 'Review') ? 'selected' : '' }}>Review</option>
                </select>
            </div>
        </div>
        <div class="row g-2 mb-2 align-items-end">
            <div class="col">
                <label class="form-label mb-0">Booking Date</label>
                <div class="d-flex gap-1">
                    <input type="number" name="booking_date_year" min="2020" max="2100" placeholder="Year" value="{{ old('booking_date_year', $filters['booking_date_year'] ?? '') }}" class="form-control" style="width: 33%;">
                    <input type="number" name="booking_date_month" min="1" max="12" placeholder="Month" value="{{ old('booking_date_month', $filters['booking_date_month'] ?? '') }}" class="form-control" style="width: 33%;">
                    <input type="number" name="booking_date_day" min="1" max="31" placeholder="Day" value="{{ old('booking_date_day', $filters['booking_date_day'] ?? '') }}" class="form-control" style="width: 33%;">
                </div>
            </div>
            <div class="col">
                <label class="form-label mb-0">Booked On</label>
                <div class="d-flex gap-1">
                    <input type="number" name="created_at_year" min="2020" max="2100" placeholder="Year" value="{{ old('created_at_year', $filters['created_at_year'] ?? '') }}" class="form-control" style="width: 33%;">
                    <input type="number" name="created_at_month" min="1" max="12" placeholder="Month" value="{{ old('created_at_month', $filters['created_at_month'] ?? '') }}" class="form-control" style="width: 33%;">
                    <input type="number" name="created_at_day" min="1" max="31" placeholder="Day" value="{{ old('created_at_day', $filters['created_at_day'] ?? '') }}" class="form-control" style="width: 33%;">
                </div>
            </div>
            <div class="col">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
            <div class="col">
                <a href="{{ route('admin.bookings.report', request()->all()) }}" class="btn btn-success w-100">Download PDF Report</a>
            </div>
        </div>
    </form>
    <div class="mb-3">
        <strong>Total Bookings:</strong> {{ $total }} |
        <strong>By Hall:</strong>
        @foreach($byHall as $hallId => $count)
            Hall #{{ $hallId }}: {{ $count }} |
        @endforeach
        <br>
        <strong>By Shift:</strong>
        @foreach($byShift as $shift => $count)
            {{ $shift }}: {{ $count }} |
        @endforeach
        <br>
        <strong>By Status:</strong>
        @foreach($byStatus as $status => $count)
            {{ $status }}: {{ $count }} |
        @endforeach
    </div>
    <div class="table-responsive">
        <table class="table table-striped" style="background:#e8eaf6;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Hall</th>
                    <th>Date</th>
                    <th>Shift</th>
                    <th>Status</th>
                    <th>Updated by</th>
                    <th>Email</th>
                    <th>Club Account</th>
                    <th>Booked On</th>
                    <th>Update Status</th>
                    <th>Make Payment</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $b)
                <tr>
                    <td>{{ $b->id }}</td>
                    <td>{{ $b->hall->name ?? $b->hall_id }}</td>
                    <td>{{ $b->booking_date }}</td>
                    <td>{{ $b->shift }}</td>
                    <td class="status-cell">{{ $b->status }}</td>
                    <td>{{ $b->statusUpdater ?? 'N/A' }}</td>
                    <td>{{ $b->member->email ?? 'N/A' }}</td>
                    <td>{{ $b->member->club_account ?? $b->club_account ?? 'N/A' }}</td>
                    <td>{{ $b->created_at ? $b->created_at->format('Y-m-d') : 'N/A' }}</td>
                    <td>
                        @if($admin && $admin->role === 'admin')
                            <button class="btn btn-sm btn-outline-warning update-status-btn" data-id="{{ $b->id }}" data-status="{{ $b->status }}">Update</button>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if($admin && $admin->role === 'admin' && in_array($b->status, ['Unpaid','Pre-Booked']))
                            <button class="btn btn-sm btn-outline-success make-payment-btn" data-id="{{ $b->id }}" data-status="{{ $b->status }}" data-hall="{{ $b->hall_id }}" data-shift="{{ $b->shift }}">Pay</button>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="11" class="text-center text-secondary">No bookings found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-center mt-3">
        {{ $bookings->withQueryString()->links('pagination::bootstrap-4') }}
    </div>
</div>

<!-- Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header">
        <h5 class="modal-title">Update Booking Status</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="statusForm">
          <input type="hidden" name="booking_id" id="status-booking-id">
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" id="status-select" class="form-select">
              <option value="">Select</option>
              <option value="Pre-Booked">Pre-Booked</option>
              <option value="Confirmed">Confirmed</option>
              <option value="Cancelled">Cancelled</option>
              <option value="Unavailable">Unavailable</option>
            </select>
          </div>
        </form>
        <div id="status-error" class="text-danger"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-warning" id="save-status-btn">Save</button>
      </div>
    </div>
  </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header">
        <h5 class="modal-title">Make Payment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="payment-info"></div>
        <div id="payment-error" class="text-danger mt-2"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" id="confirm-payment-btn">Confirm Payment</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const userRole = "{{ $admin->role ?? '' }}";

// Status Modal logic
let currentStatusBookingId = null;
let currentStatusValue = null;

$(document).on('click', '.update-status-btn', function() {
    if (userRole !== 'admin') return;
    currentStatusBookingId = $(this).data('id');
    currentStatusValue = $(this).data('status');
    $('#status-booking-id').val(currentStatusBookingId);
    $('#status-select').val(currentStatusValue);
    $('#status-error').text('');
    var modal = new bootstrap.Modal(document.getElementById('statusModal'));
    modal.show();
});

$('#save-status-btn').on('click', function() {
    if (userRole !== 'admin') return;
    const bookingId = $('#status-booking-id').val();
    const status = $('#status-select').val();
    if (!status) {
        $('#status-error').text('Please select a status.');
        return;
    }
    $.ajax({
        url: `/api/admin/bookings/${bookingId}/status`,
        method: 'PATCH',
        contentType: 'application/json',
        data: JSON.stringify({ status }),
        xhrFields: { withCredentials: true },
        success: function(res) {
            location.reload();
        },
        error: function(xhr) {
            $('#status-error').text('Failed to update status.');
        }
    });
});

// Payment Modal logic
let currentPaymentBooking = null;
let paymentPurpose = '';
let paymentAmount = 0;

$(document).on('click', '.make-payment-btn', function() {
    if (userRole !== 'admin') return;
    const bookingId = $(this).data('id');
    const status = $(this).data('status');
    const hallId = $(this).data('hall');
    const shift = $(this).data('shift');
    $('#payment-info').html('Loading charges...');
    $('#payment-error').text('');
    currentPaymentBooking = { id: bookingId, status, hall_id: hallId, shift };
    // Fetch charges
    $.ajax({
        url: '/api/calculate-charge',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ hall_id: parseInt(hallId), shift }),
        xhrFields: { withCredentials: true },
        success: function(data) {
            let amount = 0;
            if (status === 'Unpaid') {
                paymentPurpose = 'Pre-Book';
                amount = data['Pre-book'] || 0;
            } else if (status === 'Pre-Booked') {
                paymentPurpose = 'Final';
                amount = (data['total_charge'] || 0) - (data['Pre-book'] || 0);
            }
            paymentAmount = amount;
            $('#payment-info').html(`
                <div><b>Booking ID:</b> ${bookingId}</div>
                <div><b>Status:</b> ${status}</div>
                <div><b>Amount:</b> BDT ${amount}</div>
            `);
            var modal = new bootstrap.Modal(document.getElementById('paymentModal'));
            modal.show();
        },
        error: function() {
            $('#payment-info').html('Could not fetch charges.');
        }
    });
});

$('#confirm-payment-btn').on('click', function() {
    if (userRole !== 'admin') return;
    if (!currentPaymentBooking || !paymentPurpose) return;
    $('#payment-error').text('');
    $('#confirm-payment-btn').prop('disabled', true).text('Processing...');
    $.ajax({
        url: '/api/payments/manual-add',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ booking_id: currentPaymentBooking.id, purpose: paymentPurpose }),
        xhrFields: { withCredentials: true },
        success: function(res) {
            location.reload();
        },
        error: function() {
            $('#payment-error').text('Payment failed.');
            $('#confirm-payment-btn').prop('disabled', false).text('Confirm Payment');
        }
    });
});

// ...existing code...
</script>
@endsection
