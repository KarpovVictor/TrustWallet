<?php

namespace App\Http\Controllers;

use App\Models\Crypto;
use App\Models\WalletCrypto;
use App\Services\CryptoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Transaction;
use App\Services\TelegramService;

class WalletExchangeController extends Controller
{
    protected $cryptoService;

    public function __construct(CryptoService $cryptoService)
    {
        $this->middleware('auth');
        $this->cryptoService = $cryptoService;
    }

    public function exchangeView()
    {
        $user = auth()->user();
        $wallet = $user->defaultWallet();

        // Получаем криптовалюты с ненулевым балансом
        $walletCryptos = WalletCrypto::where('wallet_id', $wallet->id)
            ->where('balance', '>', 0)
            ->with('crypto')
            ->get();

        // Получаем все активные криптовалюты
        $availableCryptos = Crypto::where('is_active', true)->get();

        return view('wallet.exchange', compact('walletCryptos', 'availableCryptos'));
    }

    public function processExchange(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_crypto_id' => 'required|exists:cryptos,id',
            'to_crypto_id' => 'required|exists:cryptos,id',
            'amount' => 'required|numeric|min:0.000001'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = auth()->user();
        $wallet = $user->defaultWallet();

        // Находим криптовалюты для расчета курса
        $fromCrypto = Crypto::findOrFail($request->from_crypto_id);
        $toCrypto = Crypto::findOrFail($request->to_crypto_id);

        // Находим записи о криптовалютах
        $fromWalletCrypto = WalletCrypto::where('wallet_id', $wallet->id)
            ->where('crypto_id', $request->from_crypto_id)
            ->firstOrFail();

        // Автоматическое создание записи для криптовалюты, если она отсутствует
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

        // Проверяем баланс
        if ($fromWalletCrypto->balance < $request->amount) {
            return redirect()->back()->withErrors(['amount' => 'Недостаточно средств'])->withInput();
        }

        if ($request->from_crypto_id == $request->to_crypto_id) {
            return redirect()->back()->withErrors(['crypto' => 'Выберите разные криптовалюты'])->withInput();
        }

        $fromCryptoPrice = $fromCrypto->price;
        $minExchangeAmount = 1 / $fromCryptoPrice;

        if ($request->amount < $minExchangeAmount) {
            return redirect()->back()->withErrors([
                'amount' => "Минимальная сумма обмена: " . number_format($minExchangeAmount, 6) . " {$fromCrypto->symbol}"
            ])->withInput();
        }

        $exchangeFeePercent = 0.1;

        try {
            DB::beginTransaction();

            $fromAmountUsd = $request->amount * $fromCrypto->price;
            $receivedAmount = $fromAmountUsd / $toCrypto->price;
            
            // Применяем комиссию
            $receivedAmount *= (1 - ($exchangeFeePercent / 100));

            // Вычитаем исходную сумму
            $fromWalletCrypto->balance -= $request->amount;
            $fromWalletCrypto->save();

            // Добавляем обмененную сумму
            $toWalletCrypto->balance += $receivedAmount;
            $toWalletCrypto->save();

            // Создаем транзакции
            Transaction::create([
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

            Transaction::create([
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

            return redirect()->route('dashboard')->with('success', 'Обмен валют выполнен успешно');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['exchange' => 'Ошибка при обмене: ' . $e->getMessage()])->withInput();
        }
    }
}