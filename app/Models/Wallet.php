<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'encrypted_seed_phrase', 'is_default'
    ];

    protected $hidden = [
        'encrypted_seed_phrase'
    ];

    protected $casts = [
        'is_default' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cryptos()
    {
        return $this->hasMany(WalletCrypto::class);
    }
}
