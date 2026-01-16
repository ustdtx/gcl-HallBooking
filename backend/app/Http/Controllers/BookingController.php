<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Hall;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Helpers\SimpleLogger;
use App\Helpers\SmsHelper;


use App\Models\Member;
use App\Models\Admin;

class BookingController extends Controller
{
    /**
     * Admin Members Management Page (Blade)
     * GET/POST /admin/members
     */
    public function adminMembersPage(Request $request)
    {
        // Pagination size options
        $perPageOptions = [50, 100, 200, 300, 400, 500, 'all'];
        $perPage = $request->input('per_page', 50);
        if (!in_array($perPage, $perPageOptions)) {
            $perPage = 50;
        }

        $query = Member::query();
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }
        if ($request->filled('club_account')) {
            $query->where('club_account', 'like', '%' . $request->club_account . '%');
        }

        if ($perPage === 'all') {
            $members = $query->orderByDesc('created_at')->get();
        } else {
            $members = $query->orderByDesc('created_at')->paginate($perPage)->appends($request->all());
        }

        // For the table: id, name, club_account, email, phone, address, date_joined
        // date_joined = created_at
        return view('admin.members', [
            'members' => $members,
            'perPageOptions' => $perPageOptions,
            'filters' => [
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'club_account' => $request->input('club_account'),
                'per_page' => $perPage,
            ],
        ]);
    }

    // GET endpoint for PDF download (for admin)
    public function downloadReport(Request $request)
    {
        $filters = $request->all();
        $query = Booking::query()->with(['hall', 'member']);
        if (!empty($filters['booking_id'])) {
            $query->where('id', $filters['booking_id']);
        }
        if (!empty($filters['club_account'])) {
            $query->whereHas('member', function($q) use ($filters) {
                $q->where('club_account', 'like', '%' . $filters['club_account'] . '%');
            });
        }
        if (!empty($filters['email'])) {
            $query->whereHas('member', function($q) use ($filters) {
                $q->where('email', 'like', '%' . $filters['email'] . '%');
            });
        }
        if (!empty($filters['hall_id'])) {
            $query->where('hall_id', $filters['hall_id']);
        }
        if (!empty($filters['shift'])) {
            $query->where('shift', $filters['shift']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['booking_date_from'])) {
            $query->where('booking_date', '>=', $filters['booking_date_from']);
        }
        if (!empty($filters['booking_date_to'])) {
            $query->where('booking_date', '<=', $filters['booking_date_to']);
        }
        if (!empty($filters['created_at_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_at_from']);
        }
        if (!empty($filters['created_at_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_at_to']);
        }
        $bookings = $query->get();

        $total = $bookings->count();
        $byHall = $bookings->groupBy('hall_id')->map->count();
        $byShift = $bookings->groupBy('shift')->map->count();
        $byStatus = $bookings->groupBy('status')->map->count();
        $byPayment = $bookings->groupBy('payment_status')->map->count();

        try {
            $pdf = Pdf::loadView('booking_report', [
                'bookings' => $bookings,
                'filters' => $filters,
                'total' => $total,
                'byHall' => $byHall,
                'byShift' => $byShift,
                'byStatus' => $byStatus,
                'byPayment' => $byPayment,
            ]);
            return $pdf->download('booking_report.pdf');
        } catch (\Exception $e) {
            \Log::error('DomPDF failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'PDF generation failed: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'hall_id' => 'required|exists:halls,id',
            'booking_date' => 'required|date',
            'shift' => 'required|in:FN,AN,FD',
        ]);

        // Conflict check
        $conflict = Booking::where('hall_id', $data['hall_id'])
            ->where('booking_date', $data['booking_date'])
            ->whereIn('shift', $this->getConflictingShifts($data['shift']))
            ->where(function ($query) {
    $query->where('status', '!=', 'Cancelled')
          ->where(function ($q) {
              $q->where('status', '!=', 'Unpaid')
                ->orWhere('expires_at', '>', now());
          });
})

            ->exists();

        if ($conflict) {
            return response()->json(['error' => 'Shift already booked'], 409);
        }

        $booking = Booking::create([
            'member_id' => auth()->id(),
            'hall_id' => $data['hall_id'],
            'booking_date' => $data['booking_date'],
            'shift' => $data['shift'],
            'status' => 'Unpaid',
            'expires_at' => now()->addMinutes(15),
]);

        return response()->json($booking, 201);
    }

    public function cancel($id)
    {
        $booking = Booking::where('id', $id)->where('member_id', auth()->id())->first();

        if (!$booking) return response()->json(['error' => 'Booking not found'], 404);
        if ($booking->status === 'Cancelled') return response()->json(['message' => 'Already cancelled']);

        $booking->status = 'Cancelled';
        $booking->save();

        return response()->json(['message' => 'Booking cancelled']);
    }

    public function userBookings()
    {
        $bookings = Booking::where('member_id', auth()->id())->with('hall')->get();
        return response()->json($bookings);
    }

    public function hallBookings($hall_id, Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m'
        ]);

        $start = \Illuminate\Support\Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $bookings = Booking::where('hall_id', $hall_id)
            ->whereBetween('booking_date', [$start, $end])
            ->with('member')
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'booking_date' => $booking->booking_date,
                    'shift' => $booking->shift,
                    'status' => $booking->status,
                    'member_name' => $booking->member->name ?? 'N/A',
                    'club_account' => $booking->member->club_account ?? null,
                ];
            });

        return response()->json($bookings);
    }

    private function getConflictingShifts($shift)
    {
        return match ($shift) {
            'FD' => ['FN', 'AN', 'FD'],
            'FN', 'AN' => ['FD', $shift],
            default => [$shift]
        };
    }

    public function calculateCharge(Request $request)
{
    $data = $request->validate([
        'hall_id' => 'required|exists:halls,id',
        'shift' => 'required|in:FN,AN,FD',
    ]);
    $hall = Hall::findOrFail($data['hall_id']);
    $charges= $hall->charges ?? '{}';
    $excludedKeys = ['FN', 'AN', 'FD', 'Pre-Book'];

    $shiftCharge = (int) ($charges[$data['shift']] ?? 0);

    $extraCharges = collect($charges)
        ->except($excludedKeys)
        ->sum(function ($value) {
            return (int) $value;
        });

    $preBookCharge = (int) ($charges['Pre-Book'] ?? 0);
    $total = $shiftCharge + $extraCharges;


    return response()->json([
        'total_charge' => $total,
        'Pre-book' => $preBookCharge,
    ]);
}

public function update(Request $request, $id)
{
    $data = $request->validate([
        'booking_date' => 'required|date',
        'shift' => 'required|in:FN,AN,FD',
    ]);

    $booking = Booking::where('id', $id)
        ->where('member_id', auth()->id())
        ->first();

    if (!$booking) {
        return response()->json(['error' => 'Booking not found'], 404);
    }

    // Conflict check (exclude current booking)
    $conflict = Booking::where('hall_id', $booking->hall_id)
        ->where('booking_date', $data['booking_date'])
        ->whereIn('shift', $this->getConflictingShifts($data['shift']))
        ->where('id', '!=', $booking->id)
        ->where(function ($query) {
            $query->where('status', '!=', 'Cancelled')
                ->where(function ($q) {
                    $q->where('status', '!=', 'Unpaid')
                        ->orWhere('expires_at', '>', now());
                });
        })
        ->exists();

    if ($conflict) {
        return response()->json(['error' => 'Shift already booked'], 409);
    }

    // Store previous details before update
    $prevHall = $booking->hall ?? \App\Models\Hall::find($booking->hall_id);
    $prevHallName = $prevHall->name ?? '';
    $prevBookingDate = $booking->booking_date;
    $prevShift = $booking->shift;

    $booking->booking_date = $data['booking_date'];
    $booking->shift = $data['shift'];
    $booking->save();

    // Calculate charges after update
    $hall = $booking->hall ?? \App\Models\Hall::find($booking->hall_id);
    $charges = $hall->charges ?? [];
    if (!is_array($charges)) {
        $charges = json_decode($charges, true);
    }
    $excludedKeys = ['FN', 'AN', 'FD', 'Pre-Book'];
    $shiftCharge = (int) ($charges[$booking->shift] ?? 0);
    $extraCharges = collect($charges)
        ->except($excludedKeys)
        ->sum(function ($value) { return (int) $value; });
    $preBookCharge = (int) ($charges['Pre-Book'] ?? 0);
    $total = $shiftCharge + $extraCharges;

    // Determine amount due
    if ($booking->status === 'Unpaid') {
        $amount = $total;
    } elseif ($booking->status === 'Pre-Booked') {
        $amount = $total - $preBookCharge;
    } else {
        $amount = 0;
    }

    // Send update mail if member and email exist
    $member = $booking->member ?? null;
    $memberEmail = ($member && isset($member->email)) ? $member->email : null;
    $hallName = $hall->name ?? '';
    $bookingDate = $booking->booking_date ?? '';
    $shift = $booking->shift ?? '';
    if ($member && $memberEmail && $amount > 0 && filter_var($memberEmail, FILTER_VALIDATE_EMAIL)) {
        $smsText = "Congratulations! Your booking for {$prevHallName}, Shift: {$prevShift}, Date: {$prevBookingDate} has been updated to: Shift: {$shift}, Date: {$bookingDate}. Please complete payment of amount {$amount} BDT to complete your booking.";
        \Mail::raw($smsText,
            function ($message) use ($memberEmail, $hallName, $bookingDate, $shift) {
                $message->to($memberEmail)
                        ->subject("Gulshan Club Ltd Hall Booking Updated");
            }
        );
        // Send SMS to member
        if (!empty($member->phone) && preg_match('/^01[3-9]\d{8}$/', $member->phone)) {
            SmsHelper::send($member->phone, $smsText);
        }
        // Send similar mail and SMS to all admins
        $adminEmails = Admin::where('role', 'admin')->pluck('email')->toArray();
        $validAdminEmails = array_filter($adminEmails, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
        $adminPhones = Admin::where('role', 'admin')->pluck('phone')->toArray();
        $adminText = "[ADMIN COPY] Booking for {$prevHallName}, Shift: {$prevShift}, Date: {$prevBookingDate} has been updated to: Shift: {$shift}, Date: {$bookingDate} for member {$member->name} ({$member->email}). Amount due: {$amount} BDT.";
        if (!empty($validAdminEmails)) {
            \Mail::raw($adminText,
                function ($message) use ($validAdminEmails, $hallName, $bookingDate, $shift) {
                    $message->to($validAdminEmails)
                            ->subject("[ADMIN] Gulshan Club Ltd Hall Booking Updated");
                }
            );
        }
        if (!empty($adminPhones)) {
            foreach ($adminPhones as $adminPhone) {
                if (!empty($adminPhone) && preg_match('/^01[3-9]\d{8}$/', $adminPhone)) {
                    SmsHelper::send($adminPhone, $adminText);
                }
            }
        }
    }

    return response()->json($booking);
}

public function show($id)
{
    $booking = Booking::with(['hall', 'member'])->find($id);

    if (!$booking) {
        return response()->json(['error' => 'Booking not found'], 404);
    }

    return response()->json($booking);
}

    public function adminBlock(Request $request)
{
    $admin = \Auth::guard('admin')->user();
    $data = $request->validate([
        'hall_id' => 'required|exists:halls,id',
        'booking_date' => 'required|date',
        'shift' => 'required|in:FN,AN,FD',
    ]);

    $conflict = Booking::where('hall_id', $data['hall_id'])
        ->where('booking_date', $data['booking_date'])
        ->whereIn('shift', $this->getConflictingShifts($data['shift']))
        ->where('status', '!=', 'Cancelled')
        ->exists();

    if ($conflict) {
        return response()->json(['error' => 'Slot already booked or blocked'], 409);
    }

    $booking = Booking::create([
        'member_id' => null,
        'hall_id' => $data['hall_id'],
        'booking_date' => $data['booking_date'],
        'shift' => $data['shift'],
        'status' => 'Unavailable',
        'statusUpdater' => 'Admin',
    ]);
    if ($admin) {
        $desc = $admin->name . ' has booked an event Hall_id: ' . $data['hall_id'] . ', Booking_date: ' . $data['booking_date'] . ', Shift: ' . $data['shift'];
        SimpleLogger::log('adminBlock', $desc);
    }
    return response()->json($booking, 201);
}

public function unavailableBookings(Request $request)
{
    $query = Booking::where('status', 'Unavailable');

    if ($request->has('hall_id')) {
        $query->where('hall_id', $request->hall_id);
    }
    if ($request->has('month')) {
        $start = \Illuminate\Support\Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $query->whereBetween('booking_date', [$start, $end]);
    }

    $bookings = $query->get();

    return response()->json($bookings);
}

public function allBookings()
{
    $bookings = Booking::all();
    return response()->json($bookings);
}

    public function setToReview(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:bookings,id',
        ]);

        $booking = Booking::find($data['id']);

        $booking->status = 'Review';
        $booking->save();

        // Send mail to all admins
        $adminEmails = Admin::where('role', 'admin')->pluck('email')->toArray();
        $validAdminEmails = array_filter($adminEmails, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
        $hallName = $booking->hall->name ?? '';
        $bookingDate = $booking->booking_date ?? '';
        $shift = $booking->shift ?? '';
        $member = $booking->member ?? null;
        $memberInfo = $member ? ($member->name . ' (' . ($member->email ?? 'N/A') . ')') : 'N/A';
        $adminText = "[ADMIN NOTICE] Booking for {$hallName} Shift: {$shift} on {$bookingDate} (Member: {$memberInfo}) has requested cancellation.";
        if (!empty($validAdminEmails)) {
            \Mail::raw($adminText,
                function ($message) use ($validAdminEmails, $hallName, $bookingDate, $shift) {
                    $message->to($validAdminEmails)
                            ->subject("[ADMIN] Gulshan Club Ltd Hall Booking Cancellation Requested");
                }
            );
        }
        $adminPhones = Admin::where('role', 'admin')->pluck('phone')->toArray();
        if (!empty($adminPhones)) {
            foreach ($adminPhones as $adminPhone) {
                if (!empty($adminPhone) && preg_match('/^01[3-9]\d{8}$/', $adminPhone)) {
                    SmsHelper::send($adminPhone, $adminText);
                }
            }
        }

        return response()->json(['message' => 'Booking status set to Review', 'booking' => $booking]);
    }

    /**
     * Advanced search and filter for bookings.
     * Accepts: booking_id, club_account, email, hall_id, shift, status, booking_date_from, booking_date_to, created_at_from, created_at_to
     * All params optional, can be combined.
     * GET or POST /api/bookings/search
     */
    public function searchBookings(Request $request)
    {
        $query = Booking::query()->with(['hall', 'member']);

        // Booking ID
        if ($request->filled('booking_id')) {
            $query->where('id', $request->booking_id);
        }
        // Club account
        if ($request->filled('club_account')) {
            $query->whereHas('member', function($q) use ($request) {
                $q->where('club_account', 'like', '%' . $request->club_account . '%');
            });
        }
        // Email
        if ($request->filled('email')) {
            $query->whereHas('member', function($q) use ($request) {
                $q->where('email', 'like', '%' . $request->email . '%');
            });
        }
        // Hall
        if ($request->filled('hall_id')) {
            $query->where('hall_id', $request->hall_id);
        }
        // Shift
        if ($request->filled('shift')) {
            $query->where('shift', $request->shift);
        }
        // Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        // Booking date (from-to, supports year/month/day granularity)
        if ($request->filled('booking_date_from')) {
            $query->where('booking_date', '>=', $request->booking_date_from);
        }
        if ($request->filled('booking_date_to')) {
            $query->where('booking_date', '<=', $request->booking_date_to);
        }
        // Created at (booked on, from-to, supports year/month/day granularity)
        if ($request->filled('created_at_from')) {
            $query->whereDate('created_at', '>=', $request->created_at_from);
        }
        if ($request->filled('created_at_to')) {
            $query->whereDate('created_at', '<=', $request->created_at_to);
        }

        $bookings = $query->get();

        // Format Booked On
        $bookings->transform(function($b) {
            $b->booked_on = $b->created_at ? $b->created_at->format('Y-m-d') : null;
            return $b;
        });

        return response()->json($bookings);
    }

    /**
     * Generate PDF report for bookings based on filters.
     * POST /api/bookings/report
     * Accepts same filters as searchBookings
     */
    public function generateReport(Request $request)
    {
        // Accept filters from POST body (JSON) or query string, with POST body taking precedence
        $filters = $request->all();
        // If query string has keys not present in POST body, add them
        foreach ($request->query() as $key => $val) {
            if (!isset($filters[$key])) {
                $filters[$key] = $val;
            }
        }

        $query = Booking::query()->with(['hall', 'member']);
        if (!empty($filters['booking_id'])) {
            $query->where('id', $filters['booking_id']);
        }
        if (!empty($filters['club_account'])) {
            $query->whereHas('member', function($q) use ($filters) {
                $q->where('club_account', 'like', '%' . $filters['club_account'] . '%');
            });
        }
        if (!empty($filters['email'])) {
            $query->whereHas('member', function($q) use ($filters) {
                $q->where('email', 'like', '%' . $filters['email'] . '%');
            });
        }
        if (!empty($filters['hall_id'])) {
            $query->where('hall_id', $filters['hall_id']);
        }
        if (!empty($filters['shift'])) {
            $query->where('shift', $filters['shift']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['booking_date_from'])) {
            $query->where('booking_date', '>=', $filters['booking_date_from']);
        }
        if (!empty($filters['booking_date_to'])) {
            $query->where('booking_date', '<=', $filters['booking_date_to']);
        }
        if (!empty($filters['created_at_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_at_from']);
        }
        if (!empty($filters['created_at_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_at_to']);
        }
        $bookings = $query->get();

        // CSV generation removed (handled elsewhere)

        // Analysis (for PDF)
        $total = $bookings->count();
        $byHall = $bookings->groupBy('hall_id')->map->count();
        $byShift = $bookings->groupBy('shift')->map->count();
        $byStatus = $bookings->groupBy('status')->map->count();
        $byPayment = $bookings->groupBy('payment_status')->map->count();

        // Prepare data for PDF
        $filters = $request->all();
        $expectedFilterKeys = ['booking_id', 'club_account', 'email', 'hall_id', 'shift', 'status', 'booking_date_from', 'booking_date_to', 'created_at_from', 'created_at_to'];
        foreach ($expectedFilterKeys as $key) {
            if (!array_key_exists($key, $filters)) {
                $filters[$key] = '';
            }
        }

        // Try PDF generation, fallback to JSON error if DomPDF fails
        try {
            $pdf = Pdf::loadView('booking_report', [
                'bookings' => $bookings,
                'filters' => $filters,
                'total' => $total,
                'byHall' => $byHall,
                'byShift' => $byShift,
                'byStatus' => $byStatus,
                'byPayment' => $byPayment,
            ]);
            return $pdf->download('booking_report.pdf');
        } catch (\Exception $e) {
            \Log::error('DomPDF failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'PDF generation failed: ' . $e->getMessage(),
            ], 500);
        }
    }
 
    public function adminUpdateStatus(Request $request, $id)
    {
        $admin = \Auth::guard('admin')->user();
        if (!$admin || $admin->role !== 'admin') {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $data = $request->validate([
            'status' => 'required|in:Pre-Booked,Confirmed,Cancelled,Unavailable,Review'
        ]);
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }
        $booking->status = $data['status'];
        $booking->statusUpdater = 'Admin';
        $booking->save();

        // Send cancellation mail if status is Cancelled and member with email exists
        if ($data['status'] === 'Cancelled') {
            $member = $booking->member ?? null;
            $memberEmail = ($member && isset($member->email)) ? $member->email : null;
            $memberPhone = ($member && isset($member->phone)) ? $member->phone : null;
            $hallName = $booking->hall->name ?? '';
            $bookingDate = $booking->booking_date ?? '';
            $shift = $booking->shift ?? '';
            $userText = "Your booking for {$hallName} Shift: {$shift} on {$bookingDate} has been cancelled by the admin. If you have any queries, please contact the club office.";
            if ($member && $memberEmail && filter_var($memberEmail, FILTER_VALIDATE_EMAIL)) {
                \Mail::raw($userText,
                    function ($message) use ($memberEmail, $hallName, $bookingDate, $shift) {
                        $message->to($memberEmail)
                                ->subject("Gulshan Club Ltd Hall Booking Cancelled");
                    }
                );
                // Send SMS to member
                if (!empty($memberPhone) && preg_match('/^01[3-9]\d{8}$/', $memberPhone)) {
                    SmsHelper::send($memberPhone, $userText);
                }
                // Send similar mail and SMS to all admins
                $adminEmails = Admin::where('role', 'admin')->pluck('email')->toArray();
                $validAdminEmails = array_filter($adminEmails, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
                $adminPhones = Admin::where('role', 'admin')->pluck('phone')->toArray();
                $adminText = "[ADMIN COPY] Booking for {$hallName} Shift: {$shift} on {$bookingDate} has been cancelled by the admin. Member: {$member->name} ({$member->email}).";
                if (!empty($validAdminEmails)) {
                    \Mail::raw($adminText,
                        function ($message) use ($validAdminEmails, $hallName, $bookingDate, $shift) {
                            $message->to($validAdminEmails)
                                    ->subject("[ADMIN] Gulshan Club Ltd Hall Booking Cancelled");
                        }
                    );
                }
                if (!empty($adminPhones)) {
                    foreach ($adminPhones as $adminPhone) {
                        if (!empty($adminPhone) && preg_match('/^01[3-9]\d{8}$/', $adminPhone)) {
                            SmsHelper::send($adminPhone, $adminText);
                        }
                    }
                }
            }
        }

        $desc = $admin->name . ' updated status of booking_id ' . $id . ' to ' . $data['status'];
        SimpleLogger::log('adminUpdateStatus', $desc);
        return response()->json(['message' => 'Status updated', 'booking' => $booking]);
    }

    /**
     * Admin Booking Management Page (Blade)
     * GET/POST /admin/bookings
     */
    public function adminBookingPage(Request $request)
    {
        $halls = \App\Models\Hall::all();
        $filters = [
            'booking_id' => $request->input('booking_id'),
            'club_account' => $request->input('club_account'),
            'email' => $request->input('email'),
            'hall_id' => $request->input('hall_id'),
            'shift' => $request->input('shift'),
            'status' => $request->input('status'),
            'booking_date_year' => $request->input('booking_date_year'),
            'booking_date_month' => $request->input('booking_date_month'),
            'booking_date_day' => $request->input('booking_date_day'),
            'created_at_year' => $request->input('created_at_year'),
            'created_at_month' => $request->input('created_at_month'),
            'created_at_day' => $request->input('created_at_day'),
        ];

        $query = \App\Models\Booking::with(['hall', 'member']);
        if ($filters['booking_id']) $query->where('id', $filters['booking_id']);
        if ($filters['club_account']) $query->whereHas('member', fn($q) => $q->where('club_account', 'like', '%' . $filters['club_account'] . '%'));
        if ($filters['email']) $query->whereHas('member', fn($q) => $q->where('email', 'like', '%' . $filters['email'] . '%'));
        if ($filters['hall_id']) $query->where('hall_id', $filters['hall_id']);
        if ($filters['shift']) $query->where('shift', $filters['shift']);
        if ($filters['status']) $query->where('status', $filters['status']);

        // Booking date filter (granular)
        if ($filters['booking_date_year']) {
            if ($filters['booking_date_month']) {
                if ($filters['booking_date_day']) {
                    $date = $filters['booking_date_year'] . '-' . str_pad($filters['booking_date_month'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($filters['booking_date_day'], 2, '0', STR_PAD_LEFT);
                    $query->where('booking_date', $date);
                } else {
                    $query->whereYear('booking_date', $filters['booking_date_year'])
                          ->whereMonth('booking_date', $filters['booking_date_month']);
                }
            } else {
                $query->whereYear('booking_date', $filters['booking_date_year']);
            }
        }

        // Booked on filter (granular)
        if ($filters['created_at_year']) {
            if ($filters['created_at_month']) {
                if ($filters['created_at_day']) {
                    $date = $filters['created_at_year'] . '-' . str_pad($filters['created_at_month'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($filters['created_at_day'], 2, '0', STR_PAD_LEFT);
                    $query->whereDate('created_at', $date);
                } else {
                    $query->whereYear('created_at', $filters['created_at_year'])
                          ->whereMonth('created_at', $filters['created_at_month']);
                }
            } else {
                $query->whereYear('created_at', $filters['created_at_year']);
            }
        }

        $bookings = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(25);

        // Analysis
        $total = $bookings->count();
        $byHall = $bookings->groupBy('hall_id')->map->count();
        $byShift = $bookings->groupBy('shift')->map->count();
        $byStatus = $bookings->groupBy('status')->map->count();

        return view('admin.bookings', compact('bookings', 'halls', 'filters', 'total', 'byHall', 'byShift', 'byStatus'));
    }
}
