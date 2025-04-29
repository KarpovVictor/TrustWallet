<!-- resources/views/wallet/profiles/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 pb-20">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <a href="{{ route('settings') }}" class="mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Wallets</h1>
                </div>
                <a href="{{ route('wallet.profiles.create') }}" class="inline-flex items-center p-1 border border-transparent rounded-full shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                </a>
            </div>
        </div>
    </header>
    
    <!-- Wallets List -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="space-y-4">
            @foreach($wallets as $wallet)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="bg-blue-100 dark:bg-blue-900 rounded-full p-3 mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between items-center">
                                <h3 class="font-medium text-gray-900 dark:text-white">{{ $wallet->name }}</h3>
                                @if($wallet->is_default)
                                    <span class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs px-2 py-1 rounded">Default</span>
                                @else
                                    <form method="POST" action="{{ route('wallet.profiles.set-default', $wallet->id) }}">
                                        @csrf
                                        <button type="submit" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                            Set as Default
                                        </button>
                                    </form>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Created {{ $wallet->created_at->format('M d, Y') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection