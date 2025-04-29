<?php

namespace App\Http\Controllers;

use App\Models\Crypto;
use App\Models\WalletCrypto;
use App\Services\CryptoService;
use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\Validator;
use App\Services\TelegramService;

class WalletController extends Controller
{
    protected $cryptoService;

    public function __construct(CryptoService $cryptoService)
    {
        $this->middleware('auth');
        $this->middleware('approved')->except(['admin.user']);
        $this->cryptoService = $cryptoService;
    }

    // public function dashboard()
    // {
    //     $user = auth()->user();
    //     $wallet = $user->defaultWallet();
        
    //     if (!$wallet) {
    //         return redirect()->route('wallet.profiles.create');
    //     }
        
    //     // Получаем все доступные криптовалюты
    //     $allCryptos = Crypto::where('is_active', true)->get();
        
    //     // Получаем существующие криптовалюты пользователя
    //     $existingWalletCryptos = WalletCrypto::where('wallet_id', $wallet->id)
    //         ->with('crypto')
    //         ->get()
    //         ->keyBy('crypto_id');
        
    //     // Создаем массив для всех криптовалют (существующих и с нулевым балансом)
    //     $walletCryptos = collect();
        
    //     foreach ($allCryptos as $crypto) {
    //         if (isset($existingWalletCryptos[$crypto->id])) {
    //             // Если у пользователя уже есть эта криптовалюта
    //             $walletCryptos->push($existingWalletCryptos[$crypto->id]);
    //         } else {
    //             // Если у пользователя нет этой криптовалюты, создаем объект с нулевым балансом
    //             $walletCrypto = new WalletCrypto([
    //                 'wallet_id' => $wallet->id,
    //                 'crypto_id' => $crypto->id,
    //                 'balance' => 0,
    //                 'address' => $this->cryptoService->generateAddress($crypto->symbol)
    //             ]);
                
    //             // Устанавливаем отношение crypto вручную
    //             $walletCrypto->setRelation('crypto', $crypto);
                
    //             $walletCryptos->push($walletCrypto);
                
    //             // Опционально: можно сохранить эту запись в базу данных
    //             // $walletCrypto->save();
    //         }
    //     }
        
    //     // Сортируем криптовалюты: сначала с положительным балансом, затем по имени
    //     $walletCryptos = $walletCryptos->sortBy([
    //         // Сначала по наличию баланса (больше нуля)
    //         fn ($a, $b) => ($b->balance > 0) <=> ($a->balance > 0),
    //         // Затем по имени криптовалюты
    //         fn ($a, $b) => $a->crypto->name <=> $b->crypto->name,
    //     ]);
        
    //     $totalBalance = $this->cryptoService->calculateTotalBalance($walletCryptos);
        
    //     return view('wallet.dashboard', compact('wallet', 'walletCryptos', 'totalBalance'));
    // }

    public function dashboard()
    {
        $user = auth()->user();
        $wallet = $user->defaultWallet();
        
        if (!$wallet) {
            return redirect()->route('wallet.profiles.create');
        }
        
        // Получаем только существующие криптовалюты пользователя с ненулевым балансом
        $walletCryptos = WalletCrypto::where('wallet_id', $wallet->id)
            // ->where('balance', '>', 0) // Добавляем условие для отображения только ненулевых балансов
            ->with('crypto')
            ->get();
        
        // Сортируем криптовалюты: сначала по балансу (от большего к меньшему), затем по имени
        $walletCryptos = $walletCryptos->sortBy([
            // Сначала по балансу (по убыванию)
            fn ($a, $b) => $b->balance <=> $a->balance,
            // Затем по имени криптовалюты
            fn ($a, $b) => $a->crypto->name <=> $b->crypto->name,
        ]);
        
        $totalBalance = $this->cryptoService->calculateTotalBalance($walletCryptos);
        
        return view('wallet.dashboard', compact('wallet', 'walletCryptos', 'totalBalance'));
    }

    public function sendCrypto($symbol)
    {
        $user = auth()->user();
        $wallet = $user->defaultWallet();
        
        $crypto = Crypto::where('symbol', $symbol)->firstOrFail();
        $walletCrypto = WalletCrypto::where('wallet_id', $wallet->id)
            ->where('crypto_id', $crypto->id)
            ->firstOrFail();
            
        return view('wallet.send', compact('crypto', 'walletCrypto'));
    }

    public function sendList()
    {
        $user = auth()->user();
        $wallet = $user->defaultWallet();
        
        // Получаем криптовалюты с положительным балансом
        $walletCryptos = WalletCrypto::where('wallet_id', $wallet->id)
            ->where('balance', '>', 0)  // Только с положительным балансом
            ->with('crypto')
            ->get();
        
        return view('wallet.send-list', compact('walletCryptos'));
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
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        if (!\Hash::check($request->password, $user->password)) {
            return redirect()->back()->withErrors(['password' => 'Неверный пароль'])->withInput();
        }
        
        if ($walletCrypto->balance < $request->amount) {
            return redirect()->back()->withErrors(['amount' => 'Недостаточно средств'])->withInput();
        }
        
        try {
            $walletCrypto->balance -= $request->amount;
            $walletCrypto->save();
            
            Transaction::create([
                'user_id' => $user->id,
                'crypto_id' => $crypto->id,
                'transaction_type' => 'withdrawal',
                'amount' => -$request->amount,  // Отрицательная сумма для отправки
                'tx_hash' => $this->cryptoService->generateTxHash(),
                'status' => 'completed',
                'details' => json_encode([
                    'to_address' => $request->address
                ])
            ]);
            
            // Отправка уведомления в Telegram
            app(TelegramService::class)->sendMessage(
                "👤 Пользователь ID: {$user->id}\n📤 Отправил: {$request->amount} {$crypto->symbol}\n📝 На адрес: {$request->address}"
            );
            
            return redirect()->route('dashboard')->with('success', 'Транзакция успешно отправлена');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['send' => 'Ошибка при отправке транзакции: ' . $e->getMessage()])->withInput();
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
            });
        
        return view('wallet.receive-list', compact('walletCryptos'));
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
            return redirect()->route('receive.list')
                ->withErrors(['error' => 'Для данной криптовалюты отсутствует адрес или QR-код']);
        }
        
        // Отправка уведомления в Telegram
        app(TelegramService::class)->sendMessage(
            "👤 Пользователь ID: {$user->id}\n📥 Выбрал для пополнения: {$crypto->name} ({$crypto->symbol})\n📝 Адрес: {$crypto->address}"
        );
        
        return view('wallet.receive', compact('crypto', 'walletCrypto'));
    }

    public function settings()
    {
        $user = auth()->user();
        
        return view('wallet.settings', compact('user'));
    }

    public function updateTheme(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'theme' => 'required|in:light,dark'
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }
        
        $user = auth()->user();
        $user->update(['theme' => $request->theme]);
        
        return redirect()->back()->with('success', 'Тема успешно изменена');
    }

    public function history()
    {
        $user = auth()->user();
        $transactions = Transaction::where('user_id', $user->id)
            ->with('crypto')
            ->latest()
            ->paginate(20);
            
        return view('wallet.history', compact('transactions'));
    }
}