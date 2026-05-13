<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Signatory extends Model
{
    protected $fillable = ['position', 'full_name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
