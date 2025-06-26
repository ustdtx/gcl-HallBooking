<?php

// app/Http/Controllers/PaymentController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    public function initiate(Request $request)
{
    $data = $request->validate([
        'booking_id' => 'required|exists:bookings,id',
        'purpose' => 'required|in:Pre-Book,Final'
    ]);

    $booking = Booking::with('hall')->findOrFail($data['booking_id']);

    $charges = is_array($booking->hall->charges)
        ? $booking->hall->charges
        : json_decode($booking->hall->charges, true);

    // Extract shift charge
    $shiftCharge = (int) ($charges[$booking->shift] ?? 0);
    $preBookCharge = (int) ($charges['Pre-Book'] ?? 0);

    // Sum all charges excluding FN, AN, FD, and Pre-Book
    $excluded = ['FN', 'AN', 'FD', 'Pre-Book'];
    $extraCharges = collect($charges)
        ->except($excluded)
        ->sum(fn($value) => (int) $value);

    // Final amount logic
    $amount = $data['purpose'] === 'Pre-Book'
        ? $preBookCharge
        : ($shiftCharge + $extraCharges - $preBookCharge);

    $tran_id = uniqid('TXN_');

    Payment::create([
        'booking_id' => $booking->id,
        'type' => $data['purpose'],
        'amount' => $amount,
        'tran_id' => $tran_id,
        'status' => 'Pending'
    ]);

    $payload = [
        'store_id' => env('SSLCZ_STORE_ID'),
        'store_passwd' => env('SSLCZ_STORE_PASSWORD'),
        'total_amount' => $amount,
        'currency' => "BDT",
        'tran_id' => $tran_id,
        'success_url' => route('payment.success'),
        'fail_url' => route('payment.fail'),
        'cancel_url' => route('payment.cancel'),
        'cus_name' => auth()->user()->name,
        'cus_email' => auth()->user()->email,
        'cus_add1' => 'Dhaka',
        'cus_city' => 'Dhaka',
        'cus_country' => 'Bangladesh',
        'shipping_method' => 'NO',
        'product_name' => $booking->hall->name . ' Booking',
        'product_category' => 'Hall Booking',
        'product_profile' => 'general',
        'cus_postcode' => '1212',
        'cus_phone' => auth()->user()->phone ?? '01700000000',
        'value_a' => $booking->id,
        'value_b' => $data['purpose'],
    ];

    

    $response = Http::asForm()->post(config('services.sslcommerz.api_url'), $payload);


    if ($response->successful() && $response->json('GatewayPageURL')) {
    return response()->json([
        'gateway_url' => $response->json('GatewayPageURL')
    ]);
}


    return response()->json([
    'error' => 'SSLCommerz request failed',
    'status' => $response->status(),
    'response' => $response->body(),
], 500);
}


    public function success(Request $request)
    {
        $tran_id = $request->input('tran_id');

        $validation = Http::get(env('SSLCZ_VALIDATION_URL'), [
            'val_id' => $request->input('val_id'),
            'store_id' => env('SSLCZ_STORE_ID'),
            'store_passwd' => env('SSLCZ_STORE_PASSWORD'),
            'v' => 1,
            'format' => 'json'
        ]);

        if (!$validation->successful() || $validation->json('status') !== 'VALID') {
            return response()->json(['error' => 'Invalid transaction'], 400);
        }

        $payment = Payment::where('tran_id', $tran_id)->firstOrFail();
        $payment->status = 'Success';
        $payment->save();

        $booking = Booking::findOrFail($request->input('value_a'));

        if ($request->input('value_b') === 'Pre-Book') {
            $booking->status = 'Pre-Booked';
        } else {
            $booking->status = 'Confirmed';
        }

        $booking->save();

        return redirect(env('FRONTEND_URL') . '/reservations');
    }

    public function fail(Request $request)
    {
        Payment::where('tran_id', $request->tran_id)->update(['status' => 'Failed']);
        return response()->json(['message' => 'Payment failed']);
    }

    public function cancel(Request $request)
    {
        Payment::where('tran_id', $request->tran_id)->update(['status' => 'Cancelled']);
        return response()->json(['message' => 'Payment cancelled']);
    }

    // Add this method to your PaymentController
    public function index()
    {
        $payments = Payment::with('booking')->get();

        // Return payments with booking_id and other relevant info
        return response()->json($payments);
    }

    public function paymentsByBooking(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id'
        ]);

        $payments = Payment::where('booking_id', $request->booking_id)
            ->with('booking')
            ->get();

        return response()->json($payments);
    }
}
