<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BilanReferenceMapping extends Model
{
    protected $table = 'bilan_reference_mapping';
    
    protected $fillable = [
        'reference_code',
        'account_code', 
        'libelle'
    ];
    
    public $timestamps = true;
}