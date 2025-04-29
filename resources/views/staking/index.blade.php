@extends('layouts.app')

@section('content')
<div class="max-w-screen-xl mx-auto px-4 sm:px-6 pt-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Нативный Стейкинг</h1>
    
    <!-- Верхний блок с инструкцией -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 mb-6 flex items-center justify-between">
        <div class="flex items-center">
            <div class="mr-4">
                <img src="{{ asset('images/staking-icon.png') }}" alt="Staking" class="w-12 h-12">
            </div>
            <div>
                <p class="text-gray-800 dark:text-gray-300">Разместите ETH в стейкинге с помощью Trust</p>
                <a href="#" class="text-green-500 hover:text-green-600 font-medium">Добавить активы →</a>
            </div>
        </div>
    </div>
    
    <!-- Список криптовалют для стейкинга -->
    <div class="space-y-3">
        @foreach($cryptos as $crypto)
        <a href="{{ route('staking.crypto', $crypto->symbol) }}" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200 block">
            <div class="flex items-center">
                <div class="mr-4">
                    <img src="{{ $crypto->icon ?? asset('images/crypto/' . strtolower($crypto->symbol) . '.png') }}" alt="{{ $crypto->name }}" class="w-10 h-10 rounded-full">
                </div>
                <div>
                    <h3 class="font-medium text-gray-900 dark:text-white">{{ $crypto->name }} ({{ $crypto->symbol }})</h3>
                    <p class="text-green-500">APR до: {{ $crypto->getStakingSetting(auth()->id())->apr ?? $crypto->stakingSettings()->first()->apr ?? '0' }}%</p>
                </div>
            </div>
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endsection