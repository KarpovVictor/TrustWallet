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
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Получить криптовалюту</h1>
            </div>
        </div>
    </header>
    
    <!-- Crypto List -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($walletCryptos as $walletCrypto)
                <a href="{{ route('receive.crypto', $walletCrypto->crypto->symbol) }}" class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow duration-200">
                    <div class="p-5 flex items-center">
                        <img src="{{ asset('storage/' . $walletCrypto->crypto->icon) }}" alt="{{ $walletCrypto->crypto->symbol }}" class="h-12 w-12 mr-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ $walletCrypto->crypto->name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $walletCrypto->crypto->symbol }}</p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endsection