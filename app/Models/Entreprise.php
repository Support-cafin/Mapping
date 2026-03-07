<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entreprise extends Model
{
    protected $fillable = [
        'nom',
        'code',
        'adresse',
        'telephone',
        'email'
    ];

    public function oldAccounts() 
    { 
        return $this->hasMany(OldAccount::class); 
    }
    public function newAccounts() 
    { 
        return $this->hasMany(NewAccount::class); 
    }

    public function mappings() 
    { 
        return $this->hasMany(AccountMapping::class); 
    }

    public function oldLedger() 
    { 
        return $this->hasMany(OldGeneralLedger::class); 
    }
    public function newLedger() 
    { 
        return $this->hasMany(NewGeneralLedger::class); 
    }

    public function oldBalances() 
    { 
        return $this->hasMany(OldBalance::class); 
    }
    public function newBalances() 
    { 
        return $this->hasMany(NewBalance::class); 
    }
    
    public function balances() 
    { 
        return $this->hasMany(Balance::class); 
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

}
