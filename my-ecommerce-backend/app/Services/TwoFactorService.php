<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TwoFactorService
{
    protected $apiKey;
    protected $template;

    public function __construct()
    {
        $this->apiKey = config('services.twofactor.api_key');
        $this->template = config('services.twofactor.template');
    }

    public function sendOtp($phone)
    {
        $url = "https://2factor.in/API/V1/{$this->apiKey}/SMS/{$phone}/AUTOGEN";

        if ($this->template) {
            $url .= "/{$this->template}";
        }

        $response = Http::get($url);
        return $response->json();
    }

    public function verifyOtp($sessionId, $otp)
    {
        $url = "https://2factor.in/API/V1/{$this->apiKey}/SMS/VERIFY/{$sessionId}/{$otp}";

        $response = Http::get($url);
        return $response->json();
    }
}
