<?php
// app/Mail/LoginAttemptAlert.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginAttemptAlert extends Mailable
{
    use Queueable, SerializesModels;

    public $email;
    public $ip;
    public $user;
    public $attempts;

    public function __construct($email, $ip, $user, $attempts = 3)
    {
        $this->email = $email;
        $this->ip = $ip;
        $this->user = $user;
        $this->attempts = $attempts;
    }

    public function build()
    {
        return $this->subject('⚠️ Alert: Tentatives de connexion échouées multiples')
                    ->markdown('emails.login-attempt-alert')
                    ->with([
                        'email' => $this->email,
                        'ip' => $this->ip,
                        'user' => $this->user,
                        'attempts' => $this->attempts,
                        'time' => now()->format('d/m/Y H:i:s'),
                    ]);
    }
}