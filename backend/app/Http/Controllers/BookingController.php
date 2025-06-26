<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Hall;
use Illuminate\Support\Carbon;

class BookingController extends Controller
{
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

    $booking->booking_date = $data['booking_date'];
    $booking->shift = $data['shift'];
    $booking->save();

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

    return response()->json(['message' => 'Booking status set to Review', 'booking' => $booking]);
}
}
