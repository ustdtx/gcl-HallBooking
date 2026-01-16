<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class SmsHelper
{
    public static function send($to, $message)
    {
        $config = config('sms');
        try {
            $response = Http::post($config['sms_url'], [
                'api_token' => $config['api_token'],
                'sid' => $config['sid'],
                'msisdn' => $to,
                'sms' => $message,
                'csms_id' => uniqid(),
            ]);
            $result = $response->json();
            // Log response for debugging
            \Log::info('SMS API response', ['response' => $result]);
            return $result;
        } catch (\Exception $e) {
            \Log::error('SMS sending failed: ' . $e->getMessage());
            return false;
        }
    }
}
