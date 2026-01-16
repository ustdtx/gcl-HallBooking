<?php
namespace App\Http\Controllers;


use App\Helpers\SimpleLogger;
use App\Helpers\SmsHelper;
use Codeboxr\Nagad\Facade\NagadPayment;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Payment;

use Illuminate\Support\Facades\Http;
use App\Models\Admin;

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

        $booking = Booking::with('hall', 'member')->findOrFail($request->input('value_a'));

        // Calculate charges as in initiate()
        $charges = is_array($booking->hall->charges)
            ? $booking->hall->charges
            : json_decode($booking->hall->charges, true);
        $shiftCharge = (int) ($charges[$booking->shift] ?? 0);
        $preBookCharge = (int) ($charges['Pre-Book'] ?? 0);
        $excluded = ['FN', 'AN', 'FD', 'Pre-Book'];
        $extraCharges = collect($charges)
            ->except($excluded)
            ->sum(fn($value) => (int) $value);
        $totalAmount = $shiftCharge + $extraCharges;

        $member = $booking->member ?? $booking->user ?? null;
        $memberEmail = $member->email ?? null;
        $hallName = $booking->hall->name ?? '';
        $bookingDate = $booking->booking_date ?? '';
        $shift = $booking->shift ?? '';

        if ($request->input('value_b') === 'Pre-Book') {
            $booking->status = 'Pre-Booked';
            $booking->expires_at = now()->addHours(48);
            $remaining = $totalAmount - $preBookCharge;
            // Send Pre-Book confirmation mail
            if ($memberEmail && filter_var($memberEmail, FILTER_VALIDATE_EMAIL)) {
                \Mail::raw(
                    "Your Pre-Booking charge {$preBookCharge}BDT Transaction ID: {$tran_id} for {$hallName} Shift: {$shift} on {$bookingDate} has been received.\nTotal amount: {$totalAmount} BDT, Pre-Booking Charge: {$preBookCharge} BDT, Remaining amount: {$remaining} BDT. Please pay the remaining amount within 48 hours to confirm your booking.",
                    function ($message) use ($memberEmail, $hallName, $bookingDate, $shift) {
                        $message->to($memberEmail)
                                ->subject("Gulshan Club Ltd Hall Pre-Booking Confirmation");
                    }
                );
            }
            // Send SMS notification to member
            if ($member && !empty($member->phone) && preg_match('/^01[3-9]\d{8}$/', $member->phone)) {
                $userText = "Your Pre-Booking charge {$preBookCharge}BDT for {$hallName} Shift: {$shift} on {$bookingDate} has been received. Total amount: {$totalAmount} BDT, Pre-Booking Charge: {$preBookCharge} BDT, Remaining amount: {$remaining} BDT. Please pay the remaining amount within 48 hours to confirm your booking.";
                SmsHelper::send($member->phone, $userText);
            }
            // Send similar mail to all admins
            $adminEmails = Admin::where('role', 'admin')->pluck('email')->toArray();
            $validAdminEmails = array_filter($adminEmails, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
            if (!empty($validAdminEmails)) {
                \Mail::raw(
                    "[ADMIN COPY] Pre-Booking charge {$preBookCharge}BDT Transaction ID: {$tran_id} for {$hallName} Shift: {$shift} on {$bookingDate} has been received. Total amount: {$totalAmount} BDT, Pre-Booking Charge: {$preBookCharge} BDT, Remaining amount: {$remaining} BDT.",
                    function ($message) use ($validAdminEmails, $hallName, $bookingDate, $shift) {
                        $message->to($validAdminEmails)
                                ->subject("[ADMIN] Gulshan Club Ltd Hall Pre-Booking Confirmation");
                    }
                );
            }
            // Send SMS notification to admins
            $adminPhones = Admin::where('role', 'admin')->pluck('phone')->toArray();
            $adminText = "[ADMIN COPY] Pre-Booking charge {$preBookCharge}BDT Transaction ID: {$tran_id} for {$hallName} Shift: {$shift} on {$bookingDate} has been received. Total amount: {$totalAmount} BDT, Pre-Booking Charge: {$preBookCharge} BDT, Remaining amount: {$remaining} BDT.";
            foreach ($adminPhones as $adminPhone) {
                if (!empty($adminPhone) && preg_match('/^01[3-9]\d{8}$/', $adminPhone)) {
                    SmsHelper::send($adminPhone, $adminText);
                }
            }
        } else {
            $booking->status = 'Confirmed';
            $remaining = $totalAmount - $preBookCharge;
            // Send Final payment confirmation mail
            if ($memberEmail && filter_var($memberEmail, FILTER_VALIDATE_EMAIL)) {
                \Mail::raw(
                    "Congratulations!! Your Remaining payment {$remaining}BDT Transaction ID: {$tran_id} for {$hallName} Shift: {$shift} on {$bookingDate} has been received. Your booking has been confirmed.",
                    function ($message) use ($memberEmail, $hallName, $bookingDate, $shift) {
                        $message->to($memberEmail)
                                ->subject("Gulshan Club Ltd Hall Booking Confirmed");
                    }
                );
            }
            // Send SMS notification to member
            if ($member && !empty($member->phone) && preg_match('/^01[3-9]\d{8}$/', $member->phone)) {
                $userText = "Congratulations!! Your Remaining payment {$remaining}BDT for {$hallName} Shift: {$shift} on {$bookingDate} has been received. Your booking has been confirmed.";
                SmsHelper::send($member->phone, $userText);
            }
            // Send similar mail to all admins
            $adminEmails = Admin::where('role', 'admin')->pluck('email')->toArray();
            $validAdminEmails = array_filter($adminEmails, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
            if (!empty($validAdminEmails)) {
                \Mail::raw(
                    "[ADMIN COPY] Remaining payment {$remaining}BDT Transaction ID: {$tran_id} for {$hallName} Shift: {$shift} on {$bookingDate} has been received. Booking has been confirmed.",
                    function ($message) use ($validAdminEmails, $hallName, $bookingDate, $shift) {
                        $message->to($validAdminEmails)
                                ->subject("[ADMIN] Gulshan Club Ltd Hall Booking Confirmed");
                    }
                );
            }
            // Send SMS notification to admins
            $adminPhones = Admin::where('role', 'admin')->pluck('phone')->toArray();
            $adminText = "[ADMIN COPY] Remaining payment {$remaining}BDT Transaction ID: {$tran_id} for {$hallName} Shift: {$shift} on {$bookingDate} has been received. Booking has been confirmed.";
            foreach ($adminPhones as $adminPhone) {
                if (!empty($adminPhone) && preg_match('/^01[3-9]\d{8}$/', $adminPhone)) {
                    SmsHelper::send($adminPhone, $adminText);
                }
            }
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
    public function manualAdd(Request $request)
    {
        $admin = \Auth::guard('admin')->user();
        if (!$admin || $admin->role !== 'admin') {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $data = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'purpose' => 'required|in:Pre-Book,Final',
        ]);

        $booking = Booking::with('hall')->findOrFail($data['booking_id']);
        $adminName = $admin->name ?? 'admin';
        $now = now()->format('Ymd_His');
        $tran_id = 'TXN_' . preg_replace('/\s+/', '', $adminName) . '_' . $now;

        $charges = is_array($booking->hall->charges)
            ? $booking->hall->charges
            : json_decode($booking->hall->charges, true);

        $shiftCharge = (int) ($charges[$booking->shift] ?? 0);
        $preBookCharge = (int) ($charges['Pre-Book'] ?? 0);
        $excluded = ['FN', 'AN', 'FD', 'Pre-Book'];
        $extraCharges = collect($charges)
            ->except($excluded)
            ->sum(fn($value) => (int) $value);

        $amount = $data['purpose'] === 'Pre-Book'
            ? $preBookCharge
            : ($shiftCharge + $extraCharges - $preBookCharge);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'type' => $data['purpose'],
            'amount' => $amount,
            'tran_id' => $tran_id,
            'status' => 'Success',
        ]);

        // Update booking status
        $member = $booking->member ?? $booking->user ?? null;
        $memberEmail = ($member && isset($member->email)) ? $member->email : null;
        $hallName = $booking->hall->name ?? '';
        $bookingDate = $booking->booking_date ?? '';
        $shift = $booking->shift ?? '';
        $totalAmount = $shiftCharge + $extraCharges;
        if ($data['purpose'] === 'Pre-Book') {
            $booking->status = 'Pre-Booked';
            $booking->expires_at = now()->addHours(48);
            $remaining = $totalAmount - $preBookCharge;
            // Send Pre-Book confirmation mail for cash/manual only if member and email exist
            if ($member && $memberEmail) {
                \Mail::raw(
                    "Your cash payment of Pre-Booking charge {$preBookCharge}BDT for {$hallName} Shift: {$shift} on {$bookingDate} has been received.\nTotal amount: {$totalAmount} BDT, Pre-Booking Charge: {$preBookCharge} BDT, Remaining amount: {$remaining} BDT. Please pay the remaining amount within 48 hours to confirm your booking.",
                    function ($message) use ($memberEmail, $hallName, $bookingDate, $shift) {
                        $message->to($memberEmail)
                                ->subject("Gulshan Club Ltd Hall Pre-Booking Confirmation");
                    }
                );
            }
            // Send SMS notification to member
            if ($member && !empty($member->phone)) {
                $userText = "Your cash payment of Pre-Booking charge {$preBookCharge}BDT for {$hallName} Shift: {$shift} on {$bookingDate} has been received. Total amount: {$totalAmount} BDT, Pre-Booking Charge: {$preBookCharge} BDT, Remaining amount: {$remaining} BDT. Please pay the remaining amount within 48 hours to confirm your booking.";
                SmsHelper::send($member->phone, $userText);
            }
            // Send similar mail to all admins
            $adminEmails = Admin::where('role', 'admin')->pluck('email')->toArray();
            if (!empty($adminEmails)) {
                \Mail::raw(
                    "[ADMIN COPY] Cash payment of Pre-Booking charge {$preBookCharge}BDT for {$hallName} Shift: {$shift} on {$bookingDate} has been received. Total amount: {$totalAmount} BDT, Pre-Booking Charge: {$preBookCharge} BDT, Remaining amount: {$remaining} BDT.",
                    function ($message) use ($adminEmails, $hallName, $bookingDate, $shift) {
                        $message->to($adminEmails)
                                ->subject("[ADMIN] Gulshan Club Ltd Hall Pre-Booking Confirmation");
                    }
                );
            }
            // Send SMS notification to admins
            $adminPhones = Admin::where('role', 'admin')->pluck('phone')->toArray();
            $adminText = "[ADMIN COPY] Cash payment of Pre-Booking charge {$preBookCharge}BDT for {$hallName} Shift: {$shift} on {$bookingDate} has been received. Total amount: {$totalAmount} BDT, Pre-Booking Charge: {$preBookCharge} BDT, Remaining amount: {$remaining} BDT.";
            foreach ($adminPhones as $adminPhone) {
                if (!empty($adminPhone)) {
                    SmsHelper::send($adminPhone, $adminText);
                }
            }
        } else {
            $booking->status = 'Confirmed';
            $remaining = $totalAmount - $preBookCharge;
            // Send Final payment confirmation mail for cash/manual only if member and email exist
            if ($member && $memberEmail) {
                \Mail::raw(
                    "Congratulations!! Your cash payment of Remaining payment {$remaining}BDT for {$hallName} Shift: {$shift} on {$bookingDate} has been received. Your booking has been confirmed.",
                    function ($message) use ($memberEmail, $hallName, $bookingDate, $shift) {
                        $message->to($memberEmail)
                                ->subject("Gulshan Club Ltd Hall Booking Confirmed");
                    }
                );
            }
            // Send SMS notification to member
            if ($member && !empty($member->phone)) {
                $userText = "Congratulations!! Your cash payment of Remaining payment {$remaining}BDT for {$hallName} Shift: {$shift} on {$bookingDate} has been received. Your booking has been confirmed.";
                SmsHelper::send($member->phone, $userText);
            }
            // Send similar mail to all admins
            $adminEmails = Admin::where('role', 'admin')->pluck('email')->toArray();
            if (!empty($adminEmails)) {
                \Mail::raw(
                    "[ADMIN COPY] Cash payment of Remaining payment {$remaining}BDT for {$hallName} Shift: {$shift} on {$bookingDate} has been received. Booking has been confirmed.",
                    function ($message) use ($adminEmails, $hallName, $bookingDate, $shift) {
                        $message->to($adminEmails)
                                ->subject("[ADMIN] Gulshan Club Ltd Hall Booking Confirmed");
                    }
                );
            }
            // Send SMS notification to admins
            $adminPhones = Admin::where('role', 'admin')->pluck('phone')->toArray();
            $adminText = "[ADMIN COPY] Cash payment of Remaining payment {$remaining}BDT for {$hallName} Shift: {$shift} on {$bookingDate} has been received. Booking has been confirmed.";
            foreach ($adminPhones as $adminPhone) {
                if (!empty($adminPhone)) {
                    SmsHelper::send($adminPhone, $adminText);
                }
            }
        }

        $booking->save();

        // Log manualAdd
        $desc = $admin->name . ' made ' . $data['purpose'] . ' payment for booking_id: ' . $data['booking_id'];
        SimpleLogger::log('manualAdd', $desc);

        return response()->json([
            'message' => 'Manual payment successful',
            'payment' => $payment,
            'booking' => $booking,
        ]);
    }
public function initiateCityBank(Request $request)
{
    $data = $request->validate([
        'booking_id' => 'required|exists:bookings,id',
        'purpose' => 'required|in:Pre-Book,Final'
    ]);

    $booking = Booking::with('hall')->findOrFail($data['booking_id']);

    $charges = is_array($booking->hall->charges)
        ? $booking->hall->charges
        : json_decode($booking->hall->charges, true);

    $shiftCharge = (int) ($charges[$booking->shift] ?? 0);
    $preBookCharge = (int) ($charges['Pre-Book'] ?? 0);

    $excluded = ['FN', 'AN', 'FD', 'Pre-Book'];
    $extraCharges = collect($charges)
        ->except($excluded)
        ->sum(fn($value) => (int) $value);

    $amount = $data['purpose'] === 'Pre-Book'
        ? $preBookCharge
        : ($shiftCharge + $extraCharges - $preBookCharge);

    $tran_id = uniqid('CB_TXN_');

    Payment::create([
        'booking_id' => $booking->id,
        'type'       => $data['purpose'],
        'amount'     => $amount,
        'tran_id'    => $tran_id,
        'status'     => 'Pending'
    ]);

    $payload = [
        'merchantId'     => config('citybank.merchant_id'),
        'merchantTxnRef' => $tran_id,
        'amount'         => number_format($amount, 2, '.', ''),
        'currency'       => '050', // BDT
        'orderDesc'      => 'Hall Booking Payment',
        'returnUrl'      => route('payment.citybank.callback'),
    ];

    try {
        $response = Http::withOptions([
            'cert'    => config('citybank.cert_path'),
            'ssl_key' => config('citybank.key_path'),
            'verify'  => false, // set true if City Bank provides CA bundle
        ])
        ->withBasicAuth(config('citybank.username'), config('citybank.password'))
        ->post(config('citybank.base_url') . '/v1/createOrder', $payload);

        if ($response->successful() && isset($response['paymentUrl'])) {
            return response()->json([
                'gateway_url' => $response['paymentUrl']
            ]);
        }

        return response()->json([
            'error'    => 'City Bank request failed',
            'response' => $response->body()
        ], 500);

    } catch (\Exception $e) {
        return response()->json([
            'error'   => 'Exception during City Bank API call',
            'message' => $e->getMessage()
        ], 500);
    }
}
public function citybankCallback(Request $request)
{
    $tran_id = $request->input('merchantTxnRef');
    $status = $request->input('status'); // Expecting 'Success' or similar

    $payment = Payment::where('tran_id', $tran_id)->first();

    if (!$payment) {
        return response()->json(['error' => 'Invalid transaction reference'], 404);
    }

    if (strtolower($status) === 'success') {
        $payment->status = 'Success';
        $payment->save();

        $booking = Booking::with('hall', 'member')->findOrFail($payment->booking_id);

        // Same charge calculations as before
        $charges = is_array($booking->hall->charges)
            ? $booking->hall->charges
            : json_decode($booking->hall->charges, true);

        $shiftCharge = (int) ($charges[$booking->shift] ?? 0);
        $preBookCharge = (int) ($charges['Pre-Book'] ?? 0);
        $excluded = ['FN', 'AN', 'FD', 'Pre-Book'];
        $extraCharges = collect($charges)->except($excluded)->sum(fn($v) => (int) $v);
        $totalAmount = $shiftCharge + $extraCharges;

        $member = $booking->member ?? $booking->user ?? null;
        $memberEmail = $member->email ?? null;
        $hallName = $booking->hall->name ?? '';
        $bookingDate = $booking->booking_date ?? '';
        $shift = $booking->shift ?? '';
        $remaining = $totalAmount - $preBookCharge;

        if ($payment->type === 'Pre-Book') {
            $booking->status = 'Pre-Booked';
            $booking->expires_at = now()->addHours(48);
            // Send mail to user
            if ($memberEmail) {
                \Mail::raw(
                    "Your Pre-Booking of {$hallName} on {$bookingDate} (Shift: {$shift}) has been received. Pre-Booking Amount: {$preBookCharge} BDT. Remaining: {$remaining} BDT. Transaction ID: {$tran_id}",
                    fn($m) => $m->to($memberEmail)->subject('Pre-Booking Confirmation')
                );
            }
            // Send SMS notification to member
            if ($member && !empty($member->phone)) {
                $userText = "Your Pre-Booking of {$hallName} on {$bookingDate} (Shift: {$shift}) has been received. Pre-Booking Amount: {$preBookCharge} BDT. Remaining: {$remaining} BDT. Transaction ID: {$tran_id}";
                SmsHelper::send($member->phone, $userText);
            }
        } else {
            $booking->status = 'Confirmed';
            // Send mail to user
            if ($memberEmail) {
                \Mail::raw(
                    "Your Final payment for {$hallName} on {$bookingDate} (Shift: {$shift}) has been received. Booking confirmed. Transaction ID: {$tran_id}",
                    fn($m) => $m->to($memberEmail)->subject('Booking Confirmed')
                );
            }
            // Send SMS notification to member
            if ($member && !empty($member->phone)) {
                $userText = "Your Final payment for {$hallName} on {$bookingDate} (Shift: {$shift}) has been received. Booking confirmed. Transaction ID: {$tran_id}";
                SmsHelper::send($member->phone, $userText);
            }
        }

        $booking->save();

        return redirect(env('FRONTEND_URL') . '/reservations');
    }

    // ❌ Payment failed
    $payment->status = 'Failed';
    $payment->save();

    return redirect(env('FRONTEND_URL') . '/reservations?error=payment_failed');
}
    // ...existing code...
    public function nagadPay(Request $request)
{
    $data = $request->validate([
        'booking_id' => 'required|exists:bookings,id',
        'purpose' => 'required|in:Pre-Book,Final'
    ]);

    $booking = Booking::with('hall')->findOrFail($data['booking_id']);

    $charges = is_array($booking->hall->charges)
        ? $booking->hall->charges
        : json_decode($booking->hall->charges, true);

    $preBookCharge = (int) ($charges['Pre-Book'] ?? 0);
    $shiftCharge = (int) ($charges[$booking->shift] ?? 0);
    $extraCharges = collect($charges)->except(['FN', 'AN', 'FD', 'Pre-Book'])->sum(fn($v) => (int) $v);
    $totalAmount = $shiftCharge + $extraCharges;

    $amount = $data['purpose'] === 'Pre-Book' ? $preBookCharge : ($totalAmount - $preBookCharge);
    $invoice = 'GCN_' . uniqid(); // unique transaction ID

    // Save payment
    Payment::create([
        'booking_id' => $booking->id,
        'tran_id' => $invoice,
        'amount' => $amount,
        'status' => 'Pending',
    ]);

    return NagadPayment::create($amount, $invoice);
}
    // ...existing code...
    public function nagadCallback(Request $request)
{
    $result = NagadPayment::verify($request->paymentRefId);

    if (!isset($result['status']) || $result['status'] !== 'Success') {
        return response()->json(['error' => 'Invalid Nagad transaction'], 400);
    }

    $invoice = $result['invoiceNumber'];
    $amountPaid = (int) $result['amount'];

    $payment = Payment::where('tran_id', $invoice)->firstOrFail();
    $payment->status = 'Success';
    $payment->save();

    $booking = Booking::with('hall', 'member')->findOrFail($payment->booking_id);

    $charges = is_array($booking->hall->charges)
        ? $booking->hall->charges
        : json_decode($booking->hall->charges, true);

    $preBookCharge = (int) ($charges['Pre-Book'] ?? 0);
    $shiftCharge = (int) ($charges[$booking->shift] ?? 0);
    $extraCharges = collect($charges)->except(['FN', 'AN', 'FD', 'Pre-Book'])->sum(fn($v) => (int) $v);
    $totalAmount = $shiftCharge + $extraCharges;

    $member = $booking->member ?? $booking->user ?? null;
    $memberEmail = $member->email ?? null;
    $hallName = $booking->hall->name ?? '';
    $bookingDate = $booking->booking_date ?? '';
    $shift = $booking->shift ?? '';

    if ($amountPaid === $preBookCharge) {
        $booking->status = 'Pre-Booked';
        $booking->expires_at = now()->addHours(48);
        $remaining = $totalAmount - $preBookCharge;

        if ($memberEmail) {
            Mail::raw(
                "Your Pre-Booking charge {$preBookCharge} BDT (Txn ID: {$invoice}) for {$hallName}, {$shift} on {$bookingDate} has been received. Total: {$totalAmount} BDT. Remaining: {$remaining} BDT. Please pay within 48 hours to confirm your booking.",
                fn($m) => $m->to($memberEmail)->subject('Gulshan Club Pre-Booking Confirmation')
            );
        }
        // Send SMS notification to member
        if ($member && !empty($member->phone)) {
            $userText = "Your Pre-Booking charge {$preBookCharge} BDT (Txn ID: {$invoice}) for {$hallName}, {$shift} on {$bookingDate} has been received. Total: {$totalAmount} BDT. Remaining: {$remaining} BDT. Please pay within 48 hours to confirm your booking.";
            SmsHelper::send($member->phone, $userText);
        }

        $admins = Admin::where('role', 'admin')->pluck('email')->toArray();
        if (!empty($admins)) {
            Mail::raw(
                "[ADMIN COPY] Pre-Booking charge {$preBookCharge} BDT (Txn ID: {$invoice}) for {$hallName}, {$shift} on {$bookingDate}. Total: {$totalAmount}, Remaining: {$remaining}",
                fn($m) => $m->to($admins)->subject('[ADMIN] Pre-Booking Notification')
            );
        }
        // Send SMS notification to admins
        $adminPhones = Admin::where('role', 'admin')->pluck('phone')->toArray();
        $adminText = "[ADMIN COPY] Pre-Booking charge {$preBookCharge} BDT (Txn ID: {$invoice}) for {$hallName}, {$shift} on {$bookingDate}. Total: {$totalAmount}, Remaining: {$remaining}";
        foreach ($adminPhones as $adminPhone) {
            if (!empty($adminPhone)) {
                SmsHelper::send($adminPhone, $adminText);
            }
        }
    } else {
        $booking->status = 'Confirmed';
        $remaining = $totalAmount - $preBookCharge;

        if ($memberEmail) {
            Mail::raw(
                "Your remaining payment {$remaining} BDT (Txn ID: {$invoice}) for {$hallName}, {$shift} on {$bookingDate} has been received. Your booking is now confirmed.",
                fn($m) => $m->to($memberEmail)->subject('Gulshan Club Booking Confirmed')
            );
        }
        // Send SMS notification to member
        if ($member && !empty($member->phone)) {
            $userText = "Your remaining payment {$remaining} BDT (Txn ID: {$invoice}) for {$hallName}, {$shift} on {$bookingDate} has been received. Your booking is now confirmed.";
            SmsHelper::send($member->phone, $userText);
        }

        $admins = Admin::where('role', 'admin')->pluck('email')->toArray();
        if (!empty($admins)) {
            Mail::raw(
                "[ADMIN COPY] Remaining payment {$remaining} BDT (Txn ID: {$invoice}) for {$hallName}, {$shift} on {$bookingDate} received. Booking confirmed.",
                fn($m) => $m->to($admins)->subject('[ADMIN] Booking Confirmed')
            );
        }
        // Send SMS notification to admins
        $adminPhones = Admin::where('role', 'admin')->pluck('phone')->toArray();
        $adminText = "[ADMIN COPY] Remaining payment {$remaining} BDT (Txn ID: {$invoice}) for {$hallName}, {$shift} on {$bookingDate} received. Booking confirmed.";
        foreach ($adminPhones as $adminPhone) {
            if (!empty($adminPhone)) {
                SmsHelper::send($adminPhone, $adminText);
            }
        }
    }

    $booking->save();

    return redirect(env('FRONTEND_URL') . '/reservations');
}


}

