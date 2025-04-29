<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeedPhrase extends Model
{
    use HasFactory;

    protected $fillable = [
        'phrase', 'is_used'
    ];

    protected $hidden = [
        'phrase'
    ];

    protected $casts = [
        'is_used' => 'boolean'
    ];
}
