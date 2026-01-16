<?php
namespace App\Services;

use Xenon\NagadApi\Helper;
use Xenon\NagadApi\Base;

class NagadPaymentService
{
    protected array $config;

    public function __construct()
    {
        $this->config = [
            'NAGAD_APP_ENV' => env('NAGAD_APP_ENV', 'development'),
            'NAGAD_APP_LOG' => env('NAGAD_APP_LOG', true),
            'NAGAD_APP_MERCHANTID' => env('NAGAD_APP_MERCHANTID'),
            'NAGAD_APP_MERCHANT_PRIVATE_KEY' => env('NAGAD_APP_MERCHANT_PRIVATE_KEY'),
            'NAGAD_APP_MERCHANT_PG_PUBLIC_KEY' => env('NAGAD_APP_MERCHANT_PG_PUBLIC_KEY'),
            'NAGAD_APP_TIMEZONE' => env('NAGAD_APP_TIMEZONE', 'Asia/Dhaka'),
        ];
    }

    public function initiatePayment($amount, $invoice)
    {
        $nagad = new Base($this->config, [
            'amount' => $amount,
            'invoice' => $invoice,
            'merchantCallback' => env('NAGAD_CALLBACK_URL'),
        ]);

        return $nagad->payNowWithoutRedirection($nagad);
    }

    public function handleCallback(string $url)
    {
        return Helper::successResponse($url);
    }

    public function verify($paymentRefId)
    {
        $helper = new Helper($this->config);
        return $helper->verifyPayment($paymentRefId);
    }
}
