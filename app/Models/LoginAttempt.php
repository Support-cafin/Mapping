<?php
// app/Models/LoginAttempt.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    protected $fillable = [
        'email',
        'ip_address',
        'success',
        'user_agent',
        'reason'
    ];
    
    protected $casts = [
        'success' => 'boolean',
        'attempted_at' => 'datetime'
    ];
    
    public $timestamps = false;
}