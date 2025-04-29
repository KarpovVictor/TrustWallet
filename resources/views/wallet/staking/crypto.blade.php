<!-- resources/views/wallet/staking/crypto.blade.php -->
@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 pb-20">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center">
                <a href="{{ route('staking.index') }}" class="mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $crypto->name }} Staking</h1>
            </div>
        </div>
    </header>
    
    <!-- Staking Info -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center mb-6">
                <img src="{{ asset('storage/' . $crypto->icon) }}" alt="{{ $crypto->symbol }}" class="h-12 w-12 mr-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $crypto->name }}</h2>
                    <p class="text-gray-500 dark:text-gray-400">{{ $crypto->symbol }}</p>
                </div>
            </div>
            
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">APR</p>
                        <p class="text-lg font-medium text-gray-900 dark:text-white">{{ number_format($stakingSetting->apr, 2) }}%</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Lock period</p>
                        <p class="text-lg font-medium text-gray-900 dark:text-white">{{ $stakingSetting->lock_time_days }} days</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Min. stake amount</p>
                        <p class="text-lg font-medium text-gray-900 dark:text-white">{{ $stakingSetting->min_stake_amount }} {{ $crypto->symbol }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Available balance</p>
                        <p class="text-lg font-medium text-gray-900 dark:text-white">{{ number_format($walletCrypto->balance, 4) }} {{ $crypto->symbol }}</p>
                    </div>
                </div>
            </div>
            
            <form method="POST" action="{{ route('staking.stake', $crypto->symbol) }}">
                @csrf
                <div class="mb-4">
                    <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Amount to stake</label>
                    <div class="relative">
                        <input type="number" id="amount" name="amount" step="0.0001" min="{{ $stakingSetting->min_stake_amount }}" max="{{ $walletCrypto->balance }}" class="block w-full pl-4 pr-16 py-3 border-gray-300 dark:border-gray-700 dark:bg-gray-800 focus:ring-blue-500 focus:border-blue-500 rounded-lg shadow-sm @error('amount') border-red-500 @enderror" required value="{{ old('amount', $stakingSetting->min_stake_amount) }}">
                        <div class="absolute inset-y-0 right-0 flex items-center">
                            <button type="button" id="max_button" class="h-full px-3 text-blue-600 font-medium focus:outline-none">
                                MAX
                            </button>
                        </div>
                    </div>
                    @error('amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        ≈ ${{ number_format(old('amount', $stakingSetting->min_stake_amount) * app(App\Services\CryptoService::class)->getCryptoPrice($crypto->symbol), 2) }}
                    </p>
                </div>
                
                <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-lg mb-6">
                    <h3 class="font-medium text-gray-900 dark:text-white mb-2">Staking Summary</h3>
                    <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300 mb-1">
                        <span>Estimated monthly earnings</span>
                        <span>{{ number_format((old('amount', $stakingSetting->min_stake_amount) * $stakingSetting->apr / 100) / 12, 4) }} {{ $crypto->symbol }}</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300 mb-1">
                        <span>Estimated yearly earnings</span>
                        <span>{{ number_format((old('amount', $stakingSetting->min_stake_amount) * $stakingSetting->apr / 100), 4) }} {{ $crypto->symbol }}</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                        <span>Lock period</span>
                        <span>{{ $stakingSetting->lock_time_days }} days</span>
                    </div>
                </div>
                
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Stake Now
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('max_button').addEventListener('click', function() {
        const amountInput = document.getElementById('amount');
        amountInput.value = {{ $walletCrypto->balance }};
        
        // Trigger change event to update summary
        const event = new Event('input', { bubbles: true });
        amountInput.dispatchEvent(event);
    });
    
    // Update summary when amount changes
    document.getElementById('amount').addEventListener('input', function() {
        const amount = parseFloat(this.value) || 0;
        const apr = {{ $stakingSetting->apr }};
        const symbol = '{{ $crypto->symbol }}';
        const price = {{ app(App\Services\CryptoService::class)->getCryptoPrice($crypto->symbol) }};
        
        const monthlyEarnings = (amount * apr / 100) / 12;
        const yearlyEarnings = amount * apr / 100;
        
        document.querySelector('.bg-blue-50 .flex:nth-child(2) span:nth-child(2)').textContent = monthlyEarnings.toFixed(4) + ' ' + symbol;
        document.querySelector('.bg-blue-50 .flex:nth-child(3) span:nth-child(2)').textContent = yearlyEarnings.toFixed(4) + ' ' + symbol;
        
        // Update USD value
        document.querySelector('.text-gray-500.mt-1').textContent = '≈ $' + (amount * price).toFixed(2);
    });
</script>
@endsection