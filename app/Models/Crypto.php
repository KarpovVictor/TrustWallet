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

    protected $casts = [
        'is_active' => 'boolean'
    ];

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
