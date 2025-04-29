<!-- resources/views/wallet/send.blade.php -->
@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center">
                <a href="{{ route('dashboard') }}" class="mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Send {{ $crypto->name }}</h1>
            </div>
        </div>
    </header>
    
    <!-- Send Form -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center mb-6">
                <img src="{{ asset('storage/' . $crypto->icon) }}" alt="{{ $crypto->symbol }}" class="h-12 w-12 mr-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $crypto->name }}</h2>
                    <p class="text-gray-500 dark:text-gray-400">Balance: {{ number_format($walletCrypto->balance, 4) }} {{ $crypto->symbol }}</p>
                </div>
            </div>
            
            <form method="POST" action="{{ route('send.crypto.post', $crypto->symbol) }}" class="space-y-6">
                @csrf
                
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Recipient Address</label>
                    <div class="mt-1">
                        <div class="flex items-center">
                            <input type="text" id="address" name="address" class="block w-full px-4 py-3 border-gray-300 dark:border-gray-700 dark:bg-gray-800 focus:ring-blue-500 focus:border-blue-500 rounded-lg shadow-sm @error('address') border-red-500 @enderror" required>
                            <button type="button" class="ml-2 p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    @error('address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount</label>
                    <div class="mt-1 relative">
                        <input type="number" id="amount" name="amount" step="0.0001" min="0.0001" max="{{ $walletCrypto->balance }}" class="block w-full pl-4 pr-20 py-3 border-gray-300 dark:border-gray-700 dark:bg-gray-800 focus:ring-blue-500 focus:border-blue-500 rounded-lg shadow-sm @error('amount') border-red-500 @enderror" required>
                        <div class="absolute inset-y-0 right-0 flex items-center">
                            <span class="pr-4 text-gray-600 dark:text-gray-400">{{ $crypto->symbol }}</span>
                        </div>
                    </div>
                    @error('amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <div class="flex justify-between mt-1">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            ≈ $<span id="usd-value">0.00</span>
                        </p>
                        <button type="button" id="max_button" class="text-sm text-blue-600 hover:underline">
                            MAX
                        </button>
                    </div>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Your Password</label>
                    <div class="mt-1">
                        <input type="password" id="password" name="password" class="block w-full px-4 py-3 border-gray-300 dark:border-gray-700 dark:bg-gray-800 focus:ring-blue-500 focus:border-blue-500 rounded-lg shadow-sm @error('password') border-red-500 @enderror" required>
                    </div>
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Send {{ $crypto->symbol }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Update USD value when amount changes
    const amountInput = document.getElementById('amount');
    const usdValue = document.getElementById('usd-value');
    const price = {{ app(App\Services\CryptoService::class)->getCryptoPrice($crypto->symbol) }};
    
    amountInput.addEventListener('input', function() {
        const amount = parseFloat(this.value) || 0;
        usdValue.textContent = (amount * price).toFixed(2);
    });
    
    // Set maximum amount
    document.getElementById('max_button').addEventListener('click', function() {
        amountInput.value = {{ $walletCrypto->balance }};
        
        // Trigger input event to update USD value
        const event = new Event('input', { bubbles: true });
        amountInput.dispatchEvent(event);
    });
</script>
@endsection