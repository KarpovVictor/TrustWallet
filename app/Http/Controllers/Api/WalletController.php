<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crypto;
use App\Models\WalletCrypto;
use App\Models\Transaction;
use App\Services\CryptoService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    protected $cryptoService;

    public function __construct(CryptoService $cryptoService)
    {
        $this->cryptoService = $cryptoService;
    }

    public function dashboard()
    {
        $user = auth()->user();
        $wallet = $user->defaultWallet();
        
        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Default wallet not found'
            ], 404);
        }
        
        $walletCryptos = WalletCrypto::where('wallet_id', $wallet->id)
            ->with('crypto')
            ->get();
        
        $walletCryptos = $walletCryptos->sortBy([
            fn ($a, $b) => $b->balance <=> $a->balance,
            fn ($a, $b) => $a->crypto->name <=> $b->crypto->name,
        ])->values();
        
        $totalBalance = $this->cryptoService->calculateTotalBalance($walletCryptos);
        
        return response()->json([
            'success' => true,
            'data' => [
                'wallet' => $wallet,
                'cryptos' => $walletCryptos,
                'total_balance' => $totalBalance
            ]
        ]);
    }

    public function sendCrypto($symbol)
    {
        $user = auth()->user();
        $wallet = $user->defaultWallet();
        
        $crypto = Crypto::where('symbol', $symbol)->firstOrFail();
        $walletCrypto = WalletCrypto::where('wallet_id', $wallet->id)
            ->where('crypto_id', $crypto->id)
            ->firstOrFail();
            
        return response()->json([
            'success' => true,
            'data' => [
                'crypto' => $crypto,
                'wallet_crypto' => $walletCrypto
            ]
        ]);
    }

    public function sendList()
    {
        $user = auth()->user();
        $wallet = $user->defaultWallet();
        
        $walletCryptos = WalletCrypto::where('wallet_id', $wallet->id)
            ->where('balance', '>', 0)
            ->with('crypto')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $walletCryptos
        ]);
    }

    public function processSend(Request $request, $symbol)
    {
        $user = auth()->user();
        $wallet = $user->defaultWallet();
        
        $crypto = Crypto::where('symbol', $symbol)->firstOrFail();
        $walletCrypto = WalletCrypto::where('wallet_id', $wallet->id)
            ->where('crypto_id', $crypto->id)
            ->firstOrFail();
            
        $validator = Validator::make($request->all(), [
            'address' => 'required|string',
            'amount' => 'required|numeric|min:0.000001',
            'password' => 'required|string'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        if (!\Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password'
            ], 422);
        }
        
        if ($walletCrypto->balance < $request->amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient funds'
            ], 422);
        }
        
        try {
            $walletCrypto->balance -= $request->amount;
            $walletCrypto->save();
            
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'crypto_id' => $crypto->id,
                'transaction_type' => 'withdrawal',
                'amount' => -$request->amount,
                'tx_hash' => $this->cryptoService->generateTxHash(),
                'status' => 'completed',
                'details' => json_encode([
                    'to_address' => $request->address
                ])
            ]);
            
            app(TelegramService::class)->sendMessage(
                "👤 Пользователь ID: {$user->id}\n📤 Отправил: {$request->amount} {$crypto->symbol}\n📝 На адрес: {$request->address}"
            );
            
            return response()->json([
                'success' => true,
                'data' => $transaction,
                'message' => 'Transaction sent successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error sending transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    public function receiveList()
    {
        $user = auth()->user();
        $wallet = $user->defaultWallet();
        
        $walletCryptos = WalletCrypto::where('wallet_id', $wallet->id)
            ->with(['crypto' => function($query) {
                $query->whereNotNull('qr_code')->whereNotNull('address');
            }])
            ->get()
            ->filter(function($walletCrypto) {
                return $walletCrypto->crypto !== null;
            })
            ->values();
        
        return response()->json([
            'success' => true,
            'data' => $walletCryptos
        ]);
    }

    public function receiveCrypto($symbol)
    {
        $user = auth()->user();
        $wallet = $user->defaultWallet();
        
        $crypto = Crypto::where('symbol', $symbol)->firstOrFail();
        $walletCrypto = WalletCrypto::where('wallet_id', $wallet->id)
            ->where('crypto_id', $crypto->id)
            ->firstOrFail();

        if (empty($crypto->address) || empty($crypto->qr_code)) {
            return response()->json([
                'success' => false,
                'message' => 'There is no address or QR code for this cryptocurrency'
            ], 404);
        }
        
        app(TelegramService::class)->sendMessage(
            "👤 Пользователь ID: {$user->id}\n📥 Выбрал для пополнения: {$crypto->name} ({$crypto->symbol})\n📝 Адрес: {$crypto->address}"
        );
        
        return response()->json([
            'success' => true,
            'data' => [
                'crypto' => $crypto,
                'wallet_crypto' => $walletCrypto
            ]
        ]);
    }

    public function settings()
    {
        $user = auth()->user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'theme' => $user->theme
            ]
        ]);
    }

    public function updateTheme(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'theme' => 'required|in:light,dark'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = auth()->user();
        $user->update(['theme' => $request->theme]);
        
        return response()->json([
            'success' => true,
            'data' => [
                'theme' => $user->theme
            ],
            'message' => 'Theme has been successfully changed'
        ]);
    }

    public function history()
    {
        $user = auth()->user();
        
        $transactions = Transaction::where('user_id', $user->id)
            ->with('crypto')
            ->latest()
            ->paginate(20);
            
        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }
}