<?php

namespace Database\Seeders;

use App\Models\Crypto;
use App\Models\StakingSetting;
use Illuminate\Database\Seeder;

class StakingSettingsSeeder extends Seeder
{
    /**
     * Запустить сидер базы данных.
     */
    public function run(): void
    {
        // Массив конфигураций APR для разных криптовалют
        $stakingConfigs = [
            'BTC' => ['min_amount' => 0.001, 'apr' => 5.0, 'lock_days' => 30],
            'ETH' => ['min_amount' => 0.01, 'apr' => 6.65, 'lock_days' => 14],
            'ADA' => ['min_amount' => 10, 'apr' => 4.28, 'lock_days' => 10],
            'ALGO' => ['min_amount' => 5, 'apr' => 4.12, 'lock_days' => 7],
            'SOL' => ['min_amount' => 0.1, 'apr' => 7.5, 'lock_days' => 14],
            'DOT' => ['min_amount' => 1, 'apr' => 12.0, 'lock_days' => 30],
            'AVAX' => ['min_amount' => 0.5, 'apr' => 10.1, 'lock_days' => 21],
            'BNB' => ['min_amount' => 0.1, 'apr' => 10.22, 'lock_days' => 14],
            'ATOM' => ['min_amount' => 1, 'apr' => 17.45, 'lock_days' => 21],
            'MATIC' => ['min_amount' => 10, 'apr' => 11.5, 'lock_days' => 14],
            'APT' => ['min_amount' => 1, 'apr' => 6.28, 'lock_days' => 7],
            'ARB' => ['min_amount' => 10, 'apr' => 6.82, 'lock_days' => 7],
        ];

        // Создаем настройки стейкинга для каждой криптовалюты
        foreach ($stakingConfigs as $symbol => $config) {
            // Находим криптовалюту по символу
            $crypto = Crypto::where('symbol', $symbol)->first();
            
            if ($crypto) {
                // Создаем или обновляем настройки стейкинга для этой криптовалюты
                StakingSetting::updateOrCreate(
                    ['user_id' => null, 'crypto_id' => $crypto->id],
                    [
                        'min_stake_amount' => $config['min_amount'],
                        'apr' => $config['apr'],
                        'lock_time_days' => $config['lock_days']
                    ]
                );
                
                $this->command->info("Настройки стейкинга для {$symbol} созданы");
            } else {
                $this->command->warn("Криптовалюта {$symbol} не найдена в базе данных");
            }
        }
    }
}