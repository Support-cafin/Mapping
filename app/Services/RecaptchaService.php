<?php
// app/Services/RecaptchaService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RecaptchaService
{
    private $secretKey;
    private $siteKey;

    public function __construct()
    {
        $this->secretKey = config('services.recaptcha.secret_key');
        $this->siteKey = config('services.recaptcha.site_key');
    }

    public function verify(string $token, string $action = null): bool
    {
        if (empty($token)) {
            return false;
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $this->secretKey,
            'response' => $token,
            'remoteip' => request()->ip()
        ]);

        $data = $response->json(); 

        if (!isset($data['success']) || !$data['success']) {
            return false;
        }

        // Vérifier l'action si spécifiée
        if ($action && (!isset($data['action']) || $data['action'] !== $action)) {
            return false;
        }

        // Vérifier le score pour v3 (optionnel)
        if (isset($data['score']) && $data['score'] < 0.5) {
            return false;
        }

        return true;
    }

    public function getSiteKey(): string
    {
        return $this->siteKey;
    }
}