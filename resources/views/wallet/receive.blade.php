<!-- resources/views/wallet/receive.blade.php -->
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
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Receive {{ $crypto->name }}</h1>
            </div>
        </div>
    </header>
    
    <!-- Receive Info -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center mb-6">
                <img src="{{ asset('storage/' . $crypto->icon) }}" alt="{{ $crypto->symbol }}" class="h-12 w-12 mr-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $crypto->name }}</h2>
                    <p class="text-gray-500 dark:text-gray-400">{{ $crypto->network_name }}</p>
                </div>
            </div>
            
            <div class="flex flex-col items-center mb-6">
                <div class="bg-white p-3 rounded-lg mb-4">
                    <img src="{{ asset('storage/' . $crypto->qr_code) }}" alt="QR Code" class="h-48 w-48">
                </div>
                
                <div class="w-full bg-gray-50 dark:bg-gray-700 rounded-lg p-3 flex items-center justify-between mb-1">
                    <span class="text-gray-900 dark:text-white font-mono break-all">{{ $crypto->address }}</span>
                    <button type="button" id="copy-address" class="ml-2 text-blue-600 dark:text-blue-400 hover:text-blue-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </button>
                </div>
                
                <div id="copy-alert" class="text-green-600 dark:text-green-400 text-sm hidden">
                    Address copied to clipboard!
                </div>
            </div>
            
            <div class="bg-yellow-50 dark:bg-yellow-900/30 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-600 dark:text-yellow-400 mt-0.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Important</h3>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                            Only send {{ $crypto->symbol }} or other {{ $crypto->network_name }} tokens to this address. Sending any other coin may result in permanent loss.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-center space-x-4">
                <button type="button" id="share-address" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                    </svg>
                    Share
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Copy address to clipboard
    document.getElementById('copy-address').addEventListener('click', function() {
        const address = '{{ $walletCrypto->address }}';
        navigator.clipboard.writeText(address).then(function() {
            const copyAlert = document.getElementById('copy-alert');
            copyAlert.classList.remove('hidden');
            
            setTimeout(function() {
                copyAlert.classList.add('hidden');
            }, 3000);
        });
    });
    
    // Share address functionality
    document.getElementById('share-address').addEventListener('click', function() {
        const address = '{{ $walletCrypto->address }}';
        const title = 'My {{ $crypto->name }} ({{ $crypto->symbol }}) Address';
        
        // Use Web Share API if available
        if (navigator.share) {
            navigator.share({
                title: title,
                text: address
            }).catch(console.error);
        } else {
            // Fallback to copying to clipboard
            navigator.clipboard.writeText(address).then(function() {
                const copyAlert = document.getElementById('copy-alert');
                copyAlert.textContent = 'Address copied to clipboard for sharing!';
                copyAlert.classList.remove('hidden');
                
                setTimeout(function() {
                    copyAlert.classList.add('hidden');
                }, 3000);
            });
        }
    });
</script>
@endsection