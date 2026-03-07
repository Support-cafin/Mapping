<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class System extends Model
{
    protected $fillable = [
        'nom', 
        'description'
    ];

    public function oldAccounts()
    {
        return $this->hasMany(OldAccount::class);
    }

    public function newAccounts()
    {
        return $this->hasMany(NewAccount::class);
    }
}
