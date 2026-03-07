<?php
// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\LoginSecurityService;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['api'])->group(function () {
    Route::post('/login-security-status', function (Request $request) {
        $service = new LoginSecurityService();
        $email = $request->input('email');
        $ip = $request->ip();
        
        if (!$email) {
            return response()->json([
                'requiresCaptcha' => false,
                'attempts' => 0,
                'isBlocked' => false,
                'blockTime' => 0
            ]);
        }
        
        $attempts = $service->getFailedAttempts($email, $ip);
        
        return response()->json([
            'requiresCaptcha' => $attempts >= 2,
            'attempts' => $attempts,
            'isBlocked' => $service->isBlocked($email, $ip),
            'blockTime' => $service->getBlockTimeRemaining($email, $ip)
        ]);
    });
});