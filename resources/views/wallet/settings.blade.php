<!-- resources/views/wallet/settings.blade.php -->
@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 pb-20">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Settings</h1>
        </div>
    </header>
    
    <!-- Settings List -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <!-- Wallet Section -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Wallet</h2>
                
                <a href="{{ route('wallet.profiles.index') }}" class="flex items-center justify-between py-2">
                    <span class="text-gray-700 dark:text-gray-300">Wallets</span>
                    <div class="flex items-center">
                        <span class="text-gray-500 dark:text-gray-400 mr-2">{{ auth()->user()->wallets->count() }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </a>
            </div>
            
            <!-- Appearance Section -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Appearance</h2>
                
                <div class="flex items-center justify-between py-2">
                    <span class="text-gray-700 dark:text-gray-300">Theme</span>
                    <form method="POST" action="{{ route('settings.theme') }}" class="flex items-center" id="theme-form">
                        @csrf
                        <select name="theme" id="theme-selector" class="bg-gray-100 dark:bg-gray-700 border-0 rounded-md text-gray-700 dark:text-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="light" {{ $user->theme === 'light' ? 'selected' : '' }}>Light</option>
                            <option value="dark" {{ $user->theme === 'dark' ? 'selected' : '' }}>Dark</option>
                        </select>
                    </form>
                </div>
            </div>
            
            <!-- Security Section -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Security</h2>
                
                <a href="#" class="flex items-center justify-between py-2">
                    <span class="text-gray-700 dark:text-gray-300">Change Password</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>
                
                <a href="#" class="flex items-center justify-between py-2">
                    <span class="text-gray-700 dark:text-gray-300">Recovery Phrase</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>
            </div>
            
            <!-- Help & Support Section -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Help & Support</h2>
                
                <a href="#" class="flex items-center justify-between py-2">
                    <span class="text-gray-700 dark:text-gray-300">Help Center</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>
                
                <a href="#" class="flex items-center justify-between py-2">
                    <span class="text-gray-700 dark:text-gray-300">About</span>
                    <div class="flex items-center">
                        <span class="text-gray-500 dark:text-gray-400 mr-2">v1.0.0</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </a>
            </div>
            
            <!-- Logout -->
            <div class="p-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left text-red-600 dark:text-red-400 py-2">
                        Sign Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto-submit form when theme is changed
    document.getElementById('theme-selector').addEventListener('change', function() {
        document.getElementById('theme-form').submit();
    });
</script>
@endsection