<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletCrypto extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id', 'crypto_id', 'balance', 'address', 'private_key'
    ];

    protected $hidden = [
        'private_key'
    ];

    protected $casts = [
        'balance' => 'decimal:8'
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function crypto()
    {
        return $this->belongsTo(Crypto::class);
    }

    public function getFormattedBalanceAttribute()
    {
        return $this->balance . ' ' . $this->crypto->symbol;
    }
    
    public function getBalanceUsdAttribute()
    {
        return $this->balance * $this->crypto->price;
    }

    public function getFormattedBalanceUsdAttribute()
    {
        return '$' . number_format($this->balance_usd, 2);
    }
}
