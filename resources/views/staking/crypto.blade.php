@extends('layouts.app')

@section('content')
<div class="max-w-screen-xl mx-auto px-4 sm:px-6 pt-6 pb-24">
    <div class="flex items-center mb-6">
        <a href="{{ route('staking.index') }}" class="mr-3 text-gray-600 dark:text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="w-6 h-6 stroke-current">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Стейкинг {{ $crypto->name }}</h1>
    </div>
    
    <!-- Информация о стейкинге -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center">
                <img src="{{ $crypto->icon ?? asset('images/crypto/' . strtolower($crypto->symbol) . '.png') }}" alt="{{ $crypto->name }}" class="w-12 h-12 mr-4 rounded-full">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $crypto->name }}</h2>
                    <p class="text-gray-500 dark:text-gray-400">{{ $crypto->symbol }}</p>
                </div>
            </div>
            <div class="text-right">
                <div class="font-medium text-gray-600 dark:text-gray-300">APR</div>
                <div class="text-green-500 text-xl font-semibold">{{ $stakingSetting->apr }}%</div>
            </div>
        </div>
        
        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
            <div class="flex justify-between mb-3">
                <span class="text-gray-600 dark:text-gray-400">Доступно</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ number_format($walletCrypto->balance, 6) }} {{ $crypto->symbol }}</span>
            </div>
            <div class="flex justify-between mb-3">
                <span class="text-gray-600 dark:text-gray-400">Минимальная сумма</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ number_format($stakingSetting->min_stake_amount, 6) }} {{ $crypto->symbol }}</span>
            </div>
            <div class="flex justify-between mb-4">
                <span class="text-gray-600 dark:text-gray-400">Период блокировки</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $stakingSetting->lock_time_days }} дней</span>
            </div>
            
            <!-- Если уже есть активный стейкинг -->
            @if(isset($activeStake) && $activeStake)
                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between mb-3">
                        <span class="text-gray-600 dark:text-gray-400">В стейкинге</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ number_format($activeStake->amount, 6) }} {{ $crypto->symbol }}</span>
                    </div>
                    <div class="flex justify-between mb-3">
                        <span class="text-gray-600 dark:text-gray-400">Дата окончания</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $activeStake->end_date->format('d.m.Y') }}</span>
                    </div>
                    @if($activeStake->profit > 0)
                        <div class="flex justify-between mb-5">
                            <span class="text-gray-600 dark:text-gray-400">Накопленная прибыль</span>
                            <span class="font-medium text-green-600" style="color: #008e29;">+{{ number_format($activeStake->profit, 6) }} {{ $crypto->symbol }}</span>
                        </div>
                    @endif
                    
                    @if($activeStake->end_date <= now())
                        <form action="{{ route('staking.unstake', $activeStake->id) }}" method="POST" id="unstakeForm">
                            @csrf
                            <button type="submit" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50" id="unstakeButton">
                                Unstake
                            </button>
                        </form>
                    @else
                        <button disabled class="w-full py-3 px-4 bg-gray-300 text-gray-600 dark:bg-gray-700 dark:text-gray-400 font-medium rounded-xl cursor-not-allowed">
                            Unstake (Заблокировано)
                        </button>
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center mt-2">
                            Будет доступно {{ $activeStake->end_date->diffForHumans() }}
                        </p>
                    @endif
                </div>
            @else
                <!-- Форма стейкинга -->
                <form action="{{ route('staking.stake', $crypto->symbol) }}" method="POST" class="mt-6" id="stakeForm">
                    @csrf
                    <div class="mb-5">
                        <label for="amount" class="block text-gray-700 dark:text-gray-300 mb-2">Количество {{ $crypto->symbol }}</label>
                        <div class="flex">
                            <input type="number" name="amount" id="amount" step="0.000001" min="{{ $stakingSetting->min_stake_amount }}" 
                                   max="{{ $walletCrypto->balance }}" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-l-xl focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                   placeholder="0.0" required>
                            <button type="button" onclick="document.getElementById('amount').value='{{ $walletCrypto->balance }}'" 
                                    class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white px-4 py-2 rounded-r-xl text-gray-700 font-medium transition duration-200">
                                Max
                            </button>
                        </div>
                        @error('amount')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <button type="submit" class="w-full py-3 px-4 bg-blue-600 text-white font-medium rounded-xl transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 hover:bg-blue-700 stake-button" 
                            @if($walletCrypto->balance < $stakingSetting->min_stake_amount) disabled @endif
                            id="stakeButton">
                        Stake
                    </button>
                    
                    @if($walletCrypto->balance < $stakingSetting->min_stake_amount)
                        <p class="text-sm text-red-500 text-center mt-2">
                            Недостаточно средств для стейкинга
                        </p>
                    @endif
                </form>
            @endif
        </div>
    </div>
    
    <!-- Информация о стейкинге -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">О стейкинге {{ $crypto->name }}</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-3">
            Стейкинг - это процесс, при котором вы размещаете свои криптовалюты для поддержки операций блокчейн-сети 
            и получаете вознаграждение за это.
        </p>
        <p class="text-gray-600 dark:text-gray-400 mb-3">
            При стейкинге {{ $crypto->name }} ваши средства будут заблокированы на {{ $stakingSetting->lock_time_days }} дней, 
            в течение которых вы будете получать вознаграждение в размере {{ $stakingSetting->apr }}% годовых.
        </p>
        <p class="text-gray-600 dark:text-gray-400">
            Прибыль рассчитывается ежедневно и отображается в вашем кошельке. После окончания периода блокировки 
            вы сможете вывести свои средства и накопленную прибыль.
        </p>
    </div>
</div>
<script>
    // Анимация загрузки для кнопок стейкинга
    document.addEventListener('DOMContentLoaded', function() {
        // Функция для валидации и активации кнопки стейкинга
        const amountInput = document.getElementById('amount');
        const stakeButton = document.getElementById('stakeButton');
        
        if (amountInput && stakeButton) {
            amountInput.addEventListener('input', function() {
                const minAmount = {{ $stakingSetting->min_stake_amount }};
                const maxAmount = {{ $walletCrypto->balance }};
                const value = parseFloat(this.value);
                
                if (!isNaN(value) && value >= minAmount && value <= maxAmount) {
                    stakeButton.disabled = false;
                    stakeButton.classList.remove('bg-gray-300', 'text-gray-600', 'cursor-not-allowed');
                    stakeButton.classList.add('bg-blue-600', 'text-white', 'hover:bg-blue-700');
                } else {
                    stakeButton.disabled = true;
                    stakeButton.classList.remove('bg-blue-600', 'text-white', 'hover:bg-blue-700');
                    stakeButton.classList.add('bg-gray-300', 'text-gray-600', 'cursor-not-allowed');
                }
            });
        }
        
        // Добавление анимации лоадера к форме стейкинга
        const stakeForm = document.getElementById('stakeForm');
        if (stakeForm) {
            stakeForm.addEventListener('submit', function(e) {
                const button = this.querySelector('button[type="submit"]');
                if (button) {
                    button.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Обработка...';
                    button.disabled = true;
                }
            });
        }
        
        // Добавление анимации лоадера к форме анстейкинга
        const unstakeForm = document.getElementById('unstakeForm');
        if (unstakeForm) {
            unstakeForm.addEventListener('submit', function(e) {
                const button = this.querySelector('button[type="submit"]');
                if (button) {
                    button.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Обработка...';
                    button.disabled = true;
                }
            });
        }
    });
</script>
@endsection