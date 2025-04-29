<!-- resources/views/wallet/staking/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 pb-20">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Earn</h1>
        </div>
    </header>
    
    <!-- Cryptocurrencies List -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Staking</h2>
        
        <div class="space-y-4">
            @foreach($cryptos as $crypto)
                <a href="{{ route('staking.crypto', $crypto->symbol) }}" class="block bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <img src="{{ asset('storage/' . $crypto->icon) }}" alt="{{ $crypto->symbol }}" class="h-10 w-10 mr-4">
                        <div class="flex-1">
                            <div class="flex justify-between items-center">
                                <h3 class="font-medium text-gray-900 dark:text-white">{{ $crypto->name }} Staking</h3>
                                <div class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded text-blue-800 dark:text-blue-200 text-sm font-medium">
                                    {{ number_format($stakingSettings[$crypto->id]->apr ?? 0, 2) }}% APR
                                </div>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Earn rewards by staking your {{ $crypto->symbol }}
                            </p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        @if(count($activeStakes) > 0)
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mt-8 mb-4">Your Active Stakes</h2>
            
            <div class="space-y-4">
                @foreach($activeStakes as $stake)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <img src="{{ asset('storage/' . $stake->crypto->icon) }}" alt="{{ $stake->crypto->symbol }}" class="h-10 w-10 mr-4">
                            <div class="flex-1">
                                <div class="flex justify-between items-center">
                                    <h3 class="font-medium text-gray-900 dark:text-white">{{ $stake->crypto->name }}</h3>
                                    <div class="bg-green-100 dark:bg-green-900 px-2 py-1 rounded text-green-800 dark:text-green-200 text-sm font-medium">
                                        {{ number_format($stake->apr, 2) }}% APR
                                    </div>
                                </div>
                                <div class="flex justify-between text-sm mt-1">
                                    <p class="text-gray-500 dark:text-gray-400">Staked: {{ number_format($stake->amount, 4) }} {{ $stake->crypto->symbol }}</p>
                                    <p class="text-gray-500 dark:text-gray-400">Reward: {{ number_format($stake->profit, 4) }} {{ $stake->crypto->symbol }}</p>
                                </div>
                                <div class="mt-2">
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        @php
                                            $now = \Carbon\Carbon::now();
                                            $start = \Carbon\Carbon::parse($stake->start_date);
                                            $end = \Carbon\Carbon::parse($stake->end_date);
                                            $total = $end->diffInSeconds($start);
                                            $elapsed = $now->diffInSeconds($start);
                                            $percentage = min(100, max(0, ($elapsed / $total) * 100));
                                        @endphp
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                    </div>
                                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        <span>{{ $start->format('M d, Y') }}</span>
                                        <span>{{ $end->format('M d, Y') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection