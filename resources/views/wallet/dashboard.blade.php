<!-- resources/views/wallet/dashboard.blade.php -->
@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 pb-20">
    <!-- Header -->
    <header class="bg-blue-600 dark:bg-blue-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <select id="wallet_selector" class="bg-transparent text-white border-0 focus:ring-0 text-lg font-medium pr-8 py-1">
                        @foreach(auth()->user()->wallets as $w)
                            <option value="{{ $w->id }}" {{ $wallet->id == $w->id ? 'selected' : '' }}>
                                {{ $w->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                    <button class="text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </button>
                    <button class="text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <a class="text-white" href="{{ route('support.chat') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#fff" height="24px" width="24px" version="1.1" id="Capa_1" viewBox="0 0 60 60" xml:space="preserve">
                            <path d="M54,2H6C2.748,2,0,4.748,0,8v33c0,3.252,2.748,6,6,6h8v10c0,0.413,0.254,0.784,0.64,0.933C14.757,57.978,14.879,58,15,58  c0.276,0,0.547-0.115,0.74-0.327L25.442,47H54c3.252,0,6-2.748,6-6V8C60,4.748,57.252,2,54,2z M12,15h15c0.553,0,1,0.448,1,1  s-0.447,1-1,1H12c-0.553,0-1-0.448-1-1S11.447,15,12,15z M46,33H12c-0.553,0-1-0.448-1-1s0.447-1,1-1h34c0.553,0,1,0.448,1,1  S46.553,33,46,33z M46,25H12c-0.553,0-1-0.448-1-1s0.447-1,1-1h34c0.553,0,1,0.448,1,1S46.553,25,46,25z"/>
                        </svg>
                    </a>
                </div>
            </div>
            
            <!-- Balance Display -->
            <div class="mt-4 mb-6 text-center">
                <h2 class="text-4xl font-bold text-white">${{ number_format($totalBalance, 2) }}</h2>
                <p class="text-blue-200 dark:text-blue-300">Total Balance</p>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex justify-center space-x-8 pb-4">
                <a href="{{ route('send.list') }}" class="flex flex-col items-center text-white">
                    <div class="bg-blue-500 dark:bg-blue-800 rounded-full p-3 mb-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                        </svg>
                    </div>
                    <span class="text-sm">Send</span>
                </a>
                
                <a href="{{ route('receive.list') }}" class="flex flex-col items-center text-white">
                    <div class="bg-blue-500 dark:bg-blue-800 rounded-full p-3 mb-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                        </svg>
                    </div>
                    <span class="text-sm">Receive</span>
                </a>
                
                <a href="{{ route('staking.index') }}" class="flex flex-col items-center text-white">
                    <div class="bg-blue-500 dark:bg-blue-800 rounded-full p-3 mb-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="text-sm">Earn</span>
                </a>
                
                <a href="{{ route('wallet.exchange') }}" class="flex flex-col items-center text-white">
                    <div class="bg-blue-500 dark:bg-blue-800 rounded-full p-3 mb-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" />
                        </svg>
                    </div>
                    <span class="text-sm">Обмен</span>
                </a>
            </div>
        </div>
    </header>
    
    <!-- Tabs -->
    <div class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto">
            <div class="flex">
                <button class="px-6 py-4 text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400 font-medium">
                    Tokens
                </button>
                <button class="px-6 py-4 text-gray-500 dark:text-gray-400 font-medium">
                    NFTs
                </button>
            </div>
        </div>
    </div>
    
    <!-- Coins List -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="space-y-4">
            @foreach($walletCryptos as $walletCrypto)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="flex items-center">
                        @if(stripos($walletCrypto->crypto->icon, 'crypto-icons') !== false)
                            <img src="{{ $walletCrypto->crypto->icon }}" alt="{{ $walletCrypto->crypto->symbol }}" class="h-10 w-10 mr-4">
                        @else
                            <img src="{{ asset('storage/' . $walletCrypto->crypto->icon) }}" alt="{{ $walletCrypto->crypto->symbol }}" class="h-10 w-10 mr-4">
                        @endif
                        <div class="flex-1">
                            <div class="flex justify-between">
                                <h3 class="font-medium text-gray-900 dark:text-white">{{ $walletCrypto->crypto->name }}</h3>
                                <p class="font-medium text-gray-900 dark:text-white">{{ number_format($walletCrypto->balance, 4) }}</p>
                            </div>
                            <div class="flex justify-between text-sm">
                                <p class="text-gray-500 dark:text-gray-400">{{ $walletCrypto->crypto->symbol }}</p>
                                <p class="text-gray-500 dark:text-gray-400">${{ number_format($walletCrypto->balance * app(App\Services\CryptoService::class)->getCryptoPrice($walletCrypto->crypto->symbol), 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
    document.getElementById('wallet_selector').addEventListener('change', function() {
        window.location.href = '/wallets/' + this.value + '/set-default';
    });
</script>
@endsection