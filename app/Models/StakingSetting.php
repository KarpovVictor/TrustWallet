<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StakingSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'crypto_id', 'min_stake_amount', 'apr', 'lock_time_days'
    ];

    protected $casts = [
        'min_stake_amount' => 'decimal:8',
        'apr' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function crypto()
    {
        return $this->belongsTo(Crypto::class);
    }
}
