<?php
// app/Http/Requests/Auth/LoginRequest.php

namespace App\Http\Requests\Auth;

use App\Services\LoginSecurityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    protected $loginSecurityService;
    
    public function __construct()
    {
        $this->loginSecurityService = new LoginSecurityService();
    }
    
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        // Vérifier si bloqué
        $this->ensureNotBlocked();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            // Logger l'échec
            $this->loginSecurityService->logAttempt(
                $this->input('email'),
                $this->ip(),
                false,
                'Invalid credentials'
            );
            
            // Obtenir le nombre de tentatives
            $attempts = $this->loginSecurityService->getFailedAttempts(
                $this->input('email'),
                $this->ip()
            );
            
            // Calculer les tentatives restantes
            $remaining = 3 - $attempts;
            
            if ($remaining > 0) {
                $message = "Identifiants incorrects. Il vous reste {$remaining} tentative(s).";
            } else {
                // Bloqué
                $seconds = $this->loginSecurityService->getBlockTimeRemaining(
                    $this->input('email'),
                    $this->ip()
                );
                $minutes = ceil($seconds / 60);
                $message = "Trop de tentatives échouées. Veuillez réessayer dans {$minutes} minute(s).";
            }

            throw ValidationException::withMessages([
                'email' => $message,
            ]);
        }

        // Logger la réussite
        $this->loginSecurityService->logAttempt(
            $this->input('email'),
            $this->ip(),
            true
        );
    }
    
    public function ensureNotBlocked(): void
    {
        if ($this->loginSecurityService->isBlocked($this->input('email'), $this->ip())) {
            $seconds = $this->loginSecurityService->getBlockTimeRemaining(
                $this->input('email'),
                $this->ip()
            );
            
            $minutes = ceil($seconds / 60);
            
            throw ValidationException::withMessages([
                'email' => "Trop de tentatives échouées. Veuillez réessayer dans {$minutes} minute(s).",
            ]);
        }
    }
}