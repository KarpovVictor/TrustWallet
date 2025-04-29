<!-- resources/views/wallet/history.blade.php -->
@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 pb-20">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">History</h1>
        </div>
    </header>
    
    <!-- Transactions List -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @if($transactions->isEmpty())
            <div class="text-center py-12">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h2 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No transactions yet</h2>
                <p class="mt-2 text-gray-500 dark:text-gray-400">Your transaction history will appear here</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($transactions as $transaction)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="rounded-full p-3 mr-4
                                @if($transaction->transaction_type == 'deposit')
                                    bg-green-100 dark:bg-green-900
                                @elseif($transaction->transaction_type == 'withdrawal')
                                    bg-red-100 dark:bg-red-900
                                @elseif($transaction->transaction_type == 'stake')
                                    bg-blue-100 dark:bg-blue-900
                                @elseif($transaction->transaction_type == 'unstake')
                                    bg-purple-100 dark:bg-purple-900
                                @elseif($transaction->transaction_type == 'profit')
                                    bg-yellow-100 dark:bg-yellow-900
                                @elseif($transaction->transaction_type == 'exchange_in')
                                    bg-yellow-100 dark:bg-green-900
                                @elseif($transaction->transaction_type == 'exchange_out')
                                    bg-yellow-100 dark:bg-red-900
                                @endif
                            ">
                                @if($transaction->transaction_type == 'deposit')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                    </svg>
                                @elseif($transaction->transaction_type == 'withdrawal')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                    </svg>
                                @elseif($transaction->transaction_type == 'stake')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @elseif($transaction->transaction_type == 'unstake')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3-3m0 0l3 3m-3-3v8m0-13a9 9 0 110 18 9 9 0 010-18z" />
                                    </svg>
                                @elseif($transaction->transaction_type == 'profit')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @elseif($transaction->transaction_type == 'exchange_in')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                    </svg>
                                @elseif($transaction->transaction_type == 'exchange_out')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                    </svg>
                                @endif
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between">
                                    <h3 class="font-medium text-gray-900 dark:text-white">
                                        @if($transaction->transaction_type == 'deposit')
                                            Receive
                                        @elseif($transaction->transaction_type == 'withdrawal')
                                            Send
                                        @elseif($transaction->transaction_type == 'stake')
                                            Stake
                                        @elseif($transaction->transaction_type == 'unstake')
                                            Unstake
                                        @elseif($transaction->transaction_type == 'profit')
                                            Staking Reward
                                        @elseif($transaction->transaction_type == 'exchange_in')
                                            Exchange in
                                        @elseif($transaction->transaction_type == 'exchange_out')
                                            Exchange out
                                        @endif
                                    </h3>
                                    <p class="font-medium 
                                        @if($transaction->transaction_type == 'withdrawal' || $transaction->transaction_type == 'exchange_out')
                                            text-red-600 dark:text-red-400
                                        @else
                                            text-green-600 dark:text-green-400
                                        @endif
                                    ">
                                        @if($transaction->transaction_type == 'withdrawal')
                                            -
                                        @elseif($transaction->transaction_type  == 'exchange_out')
                                        @else
                                            +
                                        @endif
                                        {{ number_format($transaction->amount, 4) }} {{ $transaction->crypto->symbol }}
                                    </p>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <p class="text-gray-500 dark:text-gray-400">{{ $transaction->created_at->format('M d, Y H:i') }}</p>
                                    <p class="text-gray-500 dark:text-gray-400">
                                        ${{ number_format($transaction->amount * app(App\Services\CryptoService::class)->getCryptoPrice($transaction->crypto->symbol), 2) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <!-- Pagination -->
            <div class="mt-6">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection