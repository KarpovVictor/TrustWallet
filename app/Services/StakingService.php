<?php

namespace App\Services;

use App\Models\Crypto;
use App\Models\Stake;
use App\Models\StakingSetting;
use App\Models\Transaction;
use App\Models\WalletCrypto;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StakingService
{
    protected $cryptoService;

    public function __construct(CryptoService $cryptoService)
    {
        $this->cryptoService = $cryptoService;
    }

    /**
     * Stake amount of cryptocurrency.
     *
     * @param int $userId
     * @param int $cryptoId
     * @param float $amount
     * @return array
     */
    public function stakeAmount(int $userId, int $cryptoId, float $amount): array
    {
        try {
            // Get user's default wallet
            $wallet = Wallet::where('user_id', $userId)
                ->where('is_default', true)
                ->firstOrFail();
            
            // Get crypto
            $crypto = Crypto::findOrFail($cryptoId);
            
            // Get wallet crypto
            $walletCrypto = WalletCrypto::where('wallet_id', $wallet->id)
                ->where('crypto_id', $cryptoId)
                ->firstOrFail();
            
            // Get staking settings for this user and crypto
            $stakingSetting = StakingSetting::where('user_id', $userId)
                ->where('crypto_id', $cryptoId)
                ->first();
            
            // If no specific settings for user, get default settings
            if (!$stakingSetting) {
                $stakingSetting = StakingSetting::where('user_id', null)
                    ->where('crypto_id', $cryptoId)
                    ->firstOrFail();
            }
            
            // Check if amount is valid
            if ($amount < $stakingSetting->min_stake_amount) {
                return [
                    'success' => false,
                    'message' => 'Сумма стейкинга меньше минимальной: ' . $stakingSetting->min_stake_amount
                ];
            }
            
            // Check if user has enough balance
            if ($walletCrypto->balance < $amount) {
                return [
                    'success' => false,
                    'message' => 'Недостаточно средств на балансе'
                ];
            }
            
            // Check if user already has an active stake for this crypto
            $existingStake = Stake::where('user_id', $userId)
                ->where('crypto_id', $cryptoId)
                ->where('is_active', true)
                ->first();
            
            // Start database transaction
            DB::beginTransaction();
            
            // If there's an active stake, add to it
            if ($existingStake) {
                // Update existing stake
                $existingStake->amount += $amount;
                $existingStake->end_date = now()->addDays($stakingSetting->lock_time_days);
                $existingStake->lock_time_days = $stakingSetting->lock_time_days;
                $existingStake->apr = $stakingSetting->apr;
                $existingStake->save();
                
                $stake = $existingStake;
            } else {
                // Create new stake
                $stake = Stake::create([
                    'user_id' => $userId,
                    'crypto_id' => $cryptoId,
                    'wallet_id' => $wallet->id,
                    'amount' => $amount,
                    'apr' => $stakingSetting->apr,
                    'lock_time_days' => $stakingSetting->lock_time_days,
                    'profit' => 0,
                    'is_active' => true,
                    'start_date' => now(),
                    'end_date' => now()->addDays($stakingSetting->lock_time_days),
                    'last_profit_calculation' => now(),
                    'profit_snapshot' => json_encode([
                        'daily_profits' => [],
                        'total_profit' => 0
                    ])
                ]);
            }
            
            // Decrease wallet balance
            $walletCrypto->balance -= $amount;
            $walletCrypto->save();
            
            // Create transaction record
            Transaction::create([
                'user_id' => $userId,
                'crypto_id' => $cryptoId,
                'transaction_type' => 'staking',
                'amount' => $amount,
                'tx_hash' => $this->cryptoService->generateTxHash(),
                'status' => 'completed',
                'details' => json_encode([
                    'stake_id' => $stake->id,
                    'apr' => $stakingSetting->apr,
                    'lock_time_days' => $stakingSetting->lock_time_days
                ])
            ]);
            
            DB::commit();
            
            return [
                'success' => true,
                'message' => 'Стейкинг успешно выполнен',
                'stake' => $stake
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Staking error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Ошибка при выполнении стейкинга: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Unstake amount of cryptocurrency.
     *
     * @param Stake $stake
     * @return array
     */
    public function unstakeAmount(Stake $stake): array
    {
        try {
            // Check if stake is active
            if (!$stake->is_active) {
                return [
                    'success' => false,
                    'message' => 'Стейкинг уже выведен'
                ];
            }
            
            // Check if lock period has ended
            if ($stake->isLocked()) {
                return [
                    'success' => false,
                    'message' => 'Период блокировки не закончился'
                ];
            }
            
            // Get wallet crypto
            $walletCrypto = WalletCrypto::where('wallet_id', $stake->wallet_id)
                ->where('crypto_id', $stake->crypto_id)
                ->firstOrFail();
            
            // Start database transaction
            DB::beginTransaction();
            
            // Calculate total amount to return (stake amount + profit)
            $totalAmount = $stake->amount + $stake->profit;
            
            // Increase wallet balance
            $walletCrypto->balance += $totalAmount;
            $walletCrypto->save();
            
            // Mark stake as inactive
            $stake->is_active = false;
            $stake->save();
            
            // Create transaction records
            // 1. For unstaking the principal amount
            Transaction::create([
                'user_id' => $stake->user_id,
                'crypto_id' => $stake->crypto_id,
                'transaction_type' => 'unstaking',
                'amount' => $stake->amount,
                'tx_hash' => $this->cryptoService->generateTxHash(),
                'status' => 'completed',
                'details' => json_encode([
                    'stake_id' => $stake->id,
                    'type' => 'principal'
                ])
            ]);
            
            // 2. For profit amount
            if ($stake->profit > 0) {
                Transaction::create([
                    'user_id' => $stake->user_id,
                    'crypto_id' => $stake->crypto_id,
                    'transaction_type' => 'staking_reward',
                    'amount' => $stake->profit,
                    'tx_hash' => $this->cryptoService->generateTxHash(),
                    'status' => 'completed',
                    'details' => json_encode([
                        'stake_id' => $stake->id,
                        'type' => 'profit'
                    ])
                ]);
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'message' => 'Средства успешно выведены из стейкинга',
                'amount' => $totalAmount
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Unstaking error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Ошибка при выводе из стейкинга: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Calculate daily profit for all active stakes.
     *
     * @return int Number of stakes updated
     */
    public function calculateDailyProfit(): int
    {
        try {
            $count = 0;
            
            // Get all active stakes
            $activeStakes = Stake::where('is_active', true)->get();
            
            foreach ($activeStakes as $stake) {
                // Skip if already calculated today
                if ($stake->last_profit_calculation->isToday()) {
                    continue;
                }
                
                // Calculate daily profit
                $dailyProfit = $stake->calculateDailyProfit();
                
                // Update stake profit
                $stake->updateProfit($dailyProfit);
                
                $count++;
            }
            
            return $count;
        } catch (\Exception $e) {
            Log::error('Error calculating daily profit: ' . $e->getMessage());
            return 0;
        }
    }
}