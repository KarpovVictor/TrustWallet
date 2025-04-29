<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crypto;
use App\Models\WalletCrypto;
use App\Models\Transaction;
use App\Services\CryptoService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WalletExchangeController extends Controller
{
    protected $cryptoService;

    public function __construct(CryptoService $cryptoService)
    {
        $this->cryptoService = $cryptoService;
    }

    public function exchangeView()
    {
        $user = auth()->user();
        $wallet = $user->defaultWallet();

        $walletCryptos = WalletCrypto::where('wallet_id', $wallet->id)
            ->where('balance', '>', 0)
            ->with('crypto')
            ->get();

        $availableCryptos = Crypto::where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'wallet_cryptos' => $walletCryptos,
                'available_cryptos' => $availableCryptos
            ]
        ]);
    }

    public function processExchange(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_crypto_id' => 'required|exists:cryptos,id',
            'to_crypto_id' => 'required|exists:cryptos,id',
            'amount' => 'required|numeric|min:0.000001'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        $wallet = $user->defaultWallet();

        $fromCrypto = Crypto::findOrFail($request->from_crypto_id);
        $toCrypto = Crypto::findOrFail($request->to_crypto_id);

        $fromWalletCrypto = WalletCrypto::where('wallet_id', $wallet->id)
            ->where('crypto_id', $request->from_crypto_id)
            ->firstOrFail();

        $toWalletCrypto = WalletCrypto::firstOrCreate(
            [
                'wallet_id' => $wallet->id, 
                'crypto_id' => $request->to_crypto_id
            ],
            [
                'balance' => 0,
                'address' => $this->cryptoService->generateAddress($toCrypto->symbol)
            ]
        );

        if ($fromWalletCrypto->balance < $request->amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient funds'
            ], 422);
        }

        if ($request->from_crypto_id == $request->to_crypto_id) {
            return response()->json([
                'success' => false,
                'message' => 'Choose different cryptocurrencies'
            ], 422);
        }

        $fromCryptoPrice = $fromCrypto->price;
        $minExchangeAmount = 1 / $fromCryptoPrice;

        if ($request->amount < $minExchangeAmount) {
            return response()->json([
                'success' => false,
                'message' => "Minimum exchange amount: " . number_format($minExchangeAmount, 6) . " {$fromCrypto->symbol}"
            ], 422);
        }

        $exchangeFeePercent = 0.1;

        try {
            DB::beginTransaction();

            $fromAmountUsd = $request->amount * $fromCrypto->price;
            $receivedAmount = $fromAmountUsd / $toCrypto->price;
            
            $receivedAmount *= (1 - ($exchangeFeePercent / 100));

            $fromWalletCrypto->balance -= $request->amount;
            $fromWalletCrypto->save();

            $toWalletCrypto->balance += $receivedAmount;
            $toWalletCrypto->save();

            $transactionOut = Transaction::create([
                'user_id' => $user->id,
                'crypto_id' => $request->from_crypto_id,
                'transaction_type' => 'exchange_out',
                'amount' => -$request->amount,
                'tx_hash' => $this->cryptoService->generateTxHash(),
                'status' => 'completed',
                'details' => json_encode([
                    'exchanged_to' => $toCrypto->symbol,
                    'exchanged_amount' => $receivedAmount
                ])
            ]);

            $transactionIn = Transaction::create([
                'user_id' => $user->id,
                'crypto_id' => $request->to_crypto_id,
                'transaction_type' => 'exchange_in',
                'amount' => $receivedAmount,
                'tx_hash' => $this->cryptoService->generateTxHash(),
                'status' => 'completed',
                'details' => json_encode([
                    'exchanged_from' => $fromCrypto->symbol,
                    'exchanged_amount' => $request->amount
                ])
            ]);

            app(TelegramService::class)->sendMessage(
                "👤 Пользователь ID: {$user->id}\n📤 Обменял: {$request->amount} {$fromCrypto->symbol}\n📝 На: {$receivedAmount} {$toCrypto->symbol}"
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'from_transaction' => $transactionOut,
                    'to_transaction' => $transactionIn,
                    'from_amount' => $request->amount,
                    'received_amount' => $receivedAmount,
                    'from_crypto' => $fromCrypto->symbol,
                    'to_crypto' => $toCrypto->symbol
                ],
                'message' => 'Currency exchange completed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Exchange error: ' . $e->getMessage()
            ], 500);
        }
    }
}