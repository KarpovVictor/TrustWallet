<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Crypto extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol', 'name', 'full_name', 'network_name', 'icon', 'network_icon', 
        'is_active', 'address', 'qr_code', 'price'
    ];

    protected $appends = [
        'apr',
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function getAprAttribute()
    {
        if(auth()->check()) {
            $find = $this->stakingSettings()->where('user_id', auth()->id())->first();
            if($find) {
                return (float) $find->apr;
            }
        }

        return (float) $this->stakingSettings()?->first()?->apr ?? null;
    }

    public function getIconAttribute($value)
    {
        if (!$value) {
            return $value;
        }

        if (strpos($value, 'cryptoicons') !== false) {
            return '/storage/' . $value;
        }

        return $value;
    }

    public function getNetworkIconAttribute($value)
    {
        if(!$value) {
            return $value;
        }

        return '/storage/'.$value;
    }

    public function walletCryptos()
    {
        return $this->hasMany(WalletCrypto::class);
    }

    public function stakingSettings()
    {
        return $this->hasMany(StakingSetting::class);
    }

    public function stakes()
    {
        return $this->hasMany(Stake::class);
    }

    /**
     * Получить настройки стейкинга для указанного пользователя.
     *
     * @param int|null $userId ID пользователя или null для общих настроек
     * @return \App\Models\StakingSetting|null
     */
    public function getStakingSetting($userId = null)
    {
        // Сначала пытаемся найти индивидуальные настройки для пользователя
        if ($userId) {
            $settings = $this->stakingSettings()
                ->where('user_id', $userId)
                ->first();
                
            if ($settings) {
                return $settings;
            }
        }
        
        // Если индивидуальных настроек нет, возвращаем общие
        return $this->stakingSettings()
            ->whereNull('user_id')
            ->first();
    }
}
