<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crypto;
use App\Models\Stake;
use App\Models\StakingSetting;
use App\Models\WalletCrypto;
use App\Services\StakingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StakingController extends Controller
{
    protected $stakingService;

    public function __construct(StakingService $stakingService)
    {
        $this->stakingService = $stakingService;
    }

    public function index()
    {
        $user = Auth::user();
        $wallet = $user->defaultWallet();
        
        $cryptos = Crypto::where('is_active', true)
            ->orderBy('name')
            ->whereHas('stakingSettings')
            ->get();
        
        $mainCrypto = $cryptos->firstWhere('symbol', 'ETH') ?? $cryptos->first();
        
        if ($mainCrypto) {
            $cryptos = $cryptos->reject(function ($crypto) use ($mainCrypto) {
                return $crypto->id === $mainCrypto->id;
            })->prepend($mainCrypto);
        }
            
        return response()->json([
            'success' => true,
            'data' => $cryptos
        ]);
    }

    public function showCrypto($symbol)
    {
        $user = Auth::user();
        $wallet = $user->defaultWallet();
        
        $crypto = Crypto::where('symbol', $symbol)->firstOrFail();
        
        $walletCrypto = WalletCrypto::where('wallet_id', $wallet->id)
            ->where('crypto_id', $crypto->id)
            ->firstOrCreate(
                ['wallet_id' => $wallet->id, 'crypto_id' => $crypto->id],
                ['balance' => 0, 'address' => '']
            );
        
        $stakingSetting = StakingSetting::where('user_id', $user->id)
            ->where('crypto_id', $crypto->id)
            ->first();
            
        if (!$stakingSetting) {
            $stakingSetting = StakingSetting::where('user_id', null)
                ->where('crypto_id', $crypto->id)
                ->firstOrFail();
        }
        
        $activeStake = Stake::where('user_id', $user->id)
            ->where('crypto_id', $crypto->id)
            ->where('is_active', true)
            ->first();
            
        return response()->json([
            'success' => true,
            'data' => [
                'crypto' => $crypto,
                'wallet_crypto' => $walletCrypto,
                'staking_setting' => $stakingSetting,
                'active_stake' => $activeStake
            ]
        ]);
    }

    public function stake(Request $request, $symbol)
    {
        $user = Auth::user();
        $crypto = Crypto::where('symbol', $symbol)->firstOrFail();
        
        $stakingSetting = StakingSetting::where('user_id', $user->id)
            ->where('crypto_id', $crypto->id)
            ->first();
            
        if (!$stakingSetting) {
            $stakingSetting = StakingSetting::where('user_id', null)
                ->where('crypto_id', $crypto->id)
                ->firstOrFail();
        }
        
        $validator = $request->validate([
            'amount' => 'required|numeric|min:' . $stakingSetting->min_stake_amount
        ]);
        
        $result = $this->stakingService->stakeAmount(
            $user->id,
            $crypto->id,
            $request->amount
        );
        
        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Staking completed successfully'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 422);
        }
    }

    public function unstake($stakeId)
    {
        $user = Auth::user();
        
        $stake = Stake::where('id', $stakeId)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->firstOrFail();
        
        $crypto = Crypto::findOrFail($stake->crypto_id);
        
        if ($stake->isLocked()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot withdraw funds until the lock period ends.'
            ], 422);
        }
        
        $result = $this->stakingService->unstakeAmount($stake);
        
        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Funds have been successfully withdrawn from staking. Added: ' . $result['amount'] . ' ' . $crypto->symbol
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 422);
        }
    }
}