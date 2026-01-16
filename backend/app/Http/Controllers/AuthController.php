<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Helpers\SmsHelper;

class AuthController extends Controller
{
    public function requestOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'club_account' => 'required|string',
            'email_or_phone' => 'required|string',
        ]);

        if ($validator->fails()) return response()->json($validator->errors(), 422);

        $member = Member::where('club_account', $request->club_account)
            ->where(function ($query) use ($request) {
                $query->where('email', $request->email_or_phone)
                      ->orWhere('phone', $request->email_or_phone);
            })->first();

        if (!$member) return response()->json(['error' => 'Member not found'], 404);

        // Generate OTP
        $otp = rand(100000, 999999);
        $member->otp = $otp;
        $member->otp_created = now();
        $member->otp_expiry = now()->addMinutes(5);
        $member->save();

        // Send OTP via email if email is present
        if (filter_var($request->email_or_phone, FILTER_VALIDATE_EMAIL)) {
            Mail::raw("Your OTP is: $otp. This OTP will expire in 5 minutes. Please do not share this with anyone.", function ($message) use ($member) {
                $message->to($member->email)
                        ->subject('Gulshan Club Ltd Login OTP');
            });
        } else if (preg_match('/^\d{10,15}$/', $request->email_or_phone)) {
            // Send OTP via SMS if phone is present
            $smsText = "Your OTP is: $otp. This OTP will expire in 5 minutes. Please do not share this with anyone.";
            SmsHelper::send($request->email_or_phone, $smsText);
        }

        return response()->json([
            'message' => 'OTP sent successfully',
             
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'club_account' => 'required|string',
            'otp' => 'required|string',
        ]);

        if ($validator->fails()) return response()->json($validator->errors(), 422);

        $member = Member::where('club_account', $request->club_account)->first();

        if (!$member) return response()->json(['error' => 'Member not found'], 404);

        if (
            !$member->otp ||
            $member->otp !== $request->otp ||
            Carbon::now()->greaterThan($member->otp_expiry)
        ) {
            return response()->json(['error' => 'Invalid or expired OTP'], 401);
        }

        // Clear OTP
        $member->otp = null;
        $member->otp_created = null;
        $member->otp_expiry = null;
        $member->save();
        
        $token = $member->createToken('auth_token')->plainTextToken;

        // You can return a token or session logic here
        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'member' => $member
        ]);
    }
}
