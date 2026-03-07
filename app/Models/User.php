<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'entreprise_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function isAdmin()
    {
        return $this->is_admin === true;
    }

    // Vérifie si c'est un admin super (sans entreprise assignée)
    public function isSuperAdmin()
    {
        return $this->is_admin === true && $this->entreprise_id === null;
    }

    // Vérifie si c'est un admin d'entreprise
    public function isEnterpriseAdmin()
    {
        return $this->is_admin === true && $this->entreprise_id !== null;
    }

    public function getEntrepriseNomAttribute()
    {
        return $this->entreprise ? $this->entreprise->nom : 'Non attribué';
    }
}