<?php

namespace App\Http\Controllers;

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
        $this->middleware('auth');
        $this->middleware('approved')->except(['admin.user']);
        $this->stakingService = $stakingService;
    }

    /**
     * Показать список криптовалют для стейкинга
     */
    public function index()
    {
        // Получаем текущего пользователя
        $user = Auth::user();
        $wallet = $user->defaultWallet();
        
        // Получаем все активные криптовалюты, сортируем как в ТЗ
        // Сначала основная, затем по алфавиту
        $cryptos = Crypto::where('is_active', true)
            ->orderBy('name')
            ->whereHas('stakingSettings')
            ->get();
        
        // Определяем главную криптовалюту (по умолчанию ETH или первая в списке)
        $mainCrypto = $cryptos->firstWhere('symbol', 'ETH') ?? $cryptos->first();
        
        // Если найдена главная криптовалюта, помещаем её в начало коллекции
        if ($mainCrypto) {
            $cryptos = $cryptos->reject(function ($crypto) use ($mainCrypto) {
                return $crypto->id === $mainCrypto->id;
            })->prepend($mainCrypto);
        }
            
        return view('staking.index', compact('cryptos'));
    }

    /**
     * Показать страницу стейкинга для конкретной криптовалюты
     */
    public function showCrypto($symbol)
    {
        $user = Auth::user();
        $wallet = $user->defaultWallet();
        
        // Получаем криптовалюту
        $crypto = Crypto::where('symbol', $symbol)->firstOrFail();
        
        // Получаем баланс пользователя по данной криптовалюте
        $walletCrypto = WalletCrypto::where('wallet_id', $wallet->id)
            ->where('crypto_id', $crypto->id)
            ->firstOrCreate(
                ['wallet_id' => $wallet->id, 'crypto_id' => $crypto->id],
                ['balance' => 0, 'address' => '']
            );
        
        // Получаем настройки стейкинга для пользователя
        $stakingSetting = StakingSetting::where('user_id', $user->id)
            ->where('crypto_id', $crypto->id)
            ->first();
            
        // Если нет индивидуальных настроек, получаем общие настройки
        if (!$stakingSetting) {
            $stakingSetting = StakingSetting::where('user_id', null)
                ->where('crypto_id', $crypto->id)
                ->firstOrFail();
        }
        
        // Получаем активный стейкинг, если есть
        $activeStake = Stake::where('user_id', $user->id)
            ->where('crypto_id', $crypto->id)
            ->where('is_active', true)
            ->first();
            
        return view('staking.crypto', compact('crypto', 'walletCrypto', 'stakingSetting', 'activeStake'));
    }

    /**
     * Отправить криптовалюту в стейкинг
     */
    public function stake(Request $request, $symbol)
    {
        $user = Auth::user();
        $crypto = Crypto::where('symbol', $symbol)->firstOrFail();
        
        // Получаем настройки стейкинга для пользователя
        $stakingSetting = StakingSetting::where('user_id', $user->id)
            ->where('crypto_id', $crypto->id)
            ->first();
            
        // Если нет индивидуальных настроек, получаем общие настройки
        if (!$stakingSetting) {
            $stakingSetting = StakingSetting::where('user_id', null)
                ->where('crypto_id', $crypto->id)
                ->firstOrFail();
        }
        
        // Валидация запроса
        $request->validate([
            'amount' => 'required|numeric|min:' . $stakingSetting->min_stake_amount
        ]);
        
        // Выполняем стейкинг через сервис
        $result = $this->stakingService->stakeAmount(
            $user->id,
            $crypto->id,
            $request->amount
        );
        
        if ($result['success']) {
            // Если стейкинг успешен, перенаправляем обратно с сообщением об успехе
            return redirect()->route('staking.crypto', $symbol)
                ->with('success', 'Стейкинг успешно выполнен');
        } else {
            // Если ошибка, показываем сообщение об ошибке
            return redirect()->back()
                ->withErrors(['amount' => $result['message']])
                ->withInput();
        }
    }

    /**
     * Вывести криптовалюту из стейкинга
     */
    public function unstake($stakeId)
    {
        $user = Auth::user();
        
        // Получаем стейк, проверяем принадлежность пользователю
        $stake = Stake::where('id', $stakeId)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->firstOrFail();
        
        // Получаем криптовалюту для редиректа
        $crypto = Crypto::findOrFail($stake->crypto_id);
        
        // Проверка на окончание периода блокировки
        if ($stake->isLocked()) {
            return redirect()->back()
                ->withErrors(['unstake' => 'Нельзя вывести средства до окончания периода блокировки']);
        }
        
        // Выполняем вывод из стейкинга через сервис
        $result = $this->stakingService->unstakeAmount($stake);
        
        if ($result['success']) {
            // Если успешно, перенаправляем с сообщением
            return redirect()->route('staking.crypto', $crypto->symbol)
                ->with('success', 'Средства успешно выведены из стейкинга. Добавлено: ' . $result['amount'] . ' ' . $crypto->symbol);
        } else {
            // Если ошибка, показываем сообщение
            return redirect()->back()
                ->withErrors(['unstake' => $result['message']]);
        }
    }
}