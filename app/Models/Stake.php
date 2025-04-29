<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stake extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'crypto_id',
        'wallet_id',
        'amount',
        'apr',
        'lock_time_days',
        'start_date',
        'end_date',
        'profit',
        'is_active',
        'last_profit_calculation',
        'profit_snapshot'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:8',
        'apr' => 'decimal:2',
        'profit' => 'decimal:8',
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'last_profit_calculation' => 'datetime',
        'profit_snapshot' => 'json'
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($stake) {
            if (empty($stake->start_date)) {
                $stake->start_date = now();
            }
            if (empty($stake->end_date)) {
                $stake->end_date = now()->addDays($stake->lock_time_days);
            }
            if (empty($stake->last_profit_calculation)) {
                $stake->last_profit_calculation = now();
            }
            if (empty($stake->profit_snapshot)) {
                $stake->profit_snapshot = json_encode([
                    'daily_profits' => [],
                    'total_profit' => 0
                ]);
            }
        });
    }

    /**
     * Get the user associated with the stake.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the crypto associated with the stake.
     */
    public function crypto()
    {
        return $this->belongsTo(Crypto::class);
    }

    /**
     * Get the wallet associated with the stake.
     */
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Calculate daily profit based on APR.
     *
     * @return float
     */
    public function calculateDailyProfit()
    {
        // Convert APR to daily rate: APR / 365
        $dailyRate = $this->apr / 36500; // Divide by 100 (percentage) and then by 365
        
        // Calculate daily profit
        return $this->amount * $dailyRate;
    }

    /**
     * Check if the stake is locked.
     *
     * @return bool
     */
    public function isLocked()
    {
        return now() < $this->end_date;
    }

    /**
     * Get days elapsed since stake started.
     *
     * @return int
     */
    public function daysElapsed()
    {
        return max(0, $this->start_date->diffInDays(now()));
    }

    /**
     * Get remaining days of lock period.
     *
     * @return int
     */
    public function remainingDays()
    {
        if (now() > $this->end_date) {
            return 0;
        }
        
        return now()->diffInDays($this->end_date);
    }

    /**
     * Get progress percentage of staking period.
     *
     * @return float
     */
    public function progressPercentage()
    {
        $totalDays = $this->lock_time_days;
        $daysElapsed = $this->daysElapsed();
        
        return min(100, round(($daysElapsed / $totalDays) * 100, 2));
    }

    /**
     * Update profit amount and snapshot.
     *
     * @param float $dailyProfit
     * @return void
     */
    public function updateProfit(float $dailyProfit)
    {
        $profitSnapshot = json_decode($this->profit_snapshot, true);
        $date = now()->format('Y-m-d');
        
        // Add daily profit to snapshot
        $profitSnapshot['daily_profits'][$date] = $dailyProfit;
        $profitSnapshot['total_profit'] += $dailyProfit;
        
        // Update model
        $this->profit += $dailyProfit;
        $this->profit_snapshot = json_encode($profitSnapshot);
        $this->last_profit_calculation = now();
        $this->save();
    }
}