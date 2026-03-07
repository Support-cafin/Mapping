<?php
// app/Services/LoginSecurityService.php

namespace App\Services;

use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class LoginSecurityService
{
    /**
     * Enregistre une tentative de connexion
     */
    public function logAttempt(string $email, string $ip, bool $success, ?string $reason = null): void
    {
        LoginAttempt::create([
            'email' => $email,
            'ip_address' => $ip,
            'success' => $success,
            'user_agent' => request()->userAgent(),
            'reason' => $reason,
            'attempted_at' => now()
        ]);
        
        // Envoyer une alerte après 3 échecs
        if (!$success && $this->getFailedAttempts($email, $ip) == 3) {
            $this->sendAlertEmail($email, $ip);
        }
    }
    
    /**
     * Obtenir le nombre de tentatives échouées récentes (dernières 5 minutes)
     */
    public function getFailedAttempts(string $email, string $ip): int
    {
        return LoginAttempt::where('email', $email)
            ->where('ip_address', $ip)
            ->where('success', false)
            ->where('attempted_at', '>=', now()->subMinutes(5))
            ->count();
    }
    
    /**
     * Vérifier si bloqué (après 3 tentatives dans les 5 dernières minutes)
     */
    public function isBlocked(string $email, string $ip): bool
    {
        $attempts = $this->getFailedAttempts($email, $ip);
        
        if ($attempts >= 3) {
            // Vérifier la dernière tentative
            $lastAttempt = LoginAttempt::where('email', $email)
                ->where('ip_address', $ip)
                ->where('success', false)
                ->latest('attempted_at')
                ->first();
                
            if ($lastAttempt) {
                // Bloquer pendant EXACTEMENT 2 minutes après la 3ème tentative
                $blockUntil = $lastAttempt->attempted_at->addMinutes(2);
                return now()->lessThan($blockUntil);
            }
        }
        
        return false;
    }
    
    /**
     * Temps restant avant déblocage (en secondes) - MAX 120 secondes (2 minutes)
     */
    public function getBlockTimeRemaining(string $email, string $ip): int
    {
        if (!$this->isBlocked($email, $ip)) {
            return 0;
        }
        
        $lastAttempt = LoginAttempt::where('email', $email)
            ->where('ip_address', $ip)
            ->where('success', false)
            ->latest('attempted_at')
            ->first();
            
        if (!$lastAttempt) {
            return 0;
        }
        
        $blockUntil = $lastAttempt->attempted_at->addMinutes(2);
        $remaining = now()->diffInSeconds($blockUntil, false);
        
        // S'assurer que le temps restant est entre 0 et 120 secondes
        return max(0, min($remaining, 120));
    }
    
    /**
     * Envoie un email d'alerte
     */
    public function sendAlertEmail(string $email, string $ip): void
    {
        try {
            $user = User::where('email', $email)->first();
            
            if ($user && config('mail.admin_email')) {
                Mail::to(config('mail.admin_email'))
                    ->send(new \App\Mail\LoginAttemptAlert($email, $ip, $user, 3));
            }
        } catch (\Exception $e) {
            \Log::error('Erreur envoi email alerte: ' . $e->getMessage());
        }
    }
    
    /**
     * Nettoyer les tentatives anciennes (pour éviter l'accumulation)
     */
    public function cleanupOldAttempts(): void
    {
        // Supprimer les tentatives de plus de 30 minutes
        LoginAttempt::where('attempted_at', '<', now()->subMinutes(30))->delete();
    }
}