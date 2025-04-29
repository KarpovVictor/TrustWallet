@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 pb-20">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center">
                <a href="{{ route('wallet.profiles.index') }}" class="mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Add Wallet</h1>
            </div>
        </div>
    </header>
    
    <!-- Add Wallet Container -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Select an option</h2>
            
            <!-- Wallet Creation Options -->
            <div class="grid md:grid-cols-2 gap-4 mb-6">
                <div id="create-wallet-option" class="wallet-option @if(!$errors->import->any()) selected @endif">
                    <div class="bg-blue-100 dark:bg-blue-900 rounded-full p-3 mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="font-medium text-gray-900 dark:text-white">Create New Wallet</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Generate a new wallet and seed phrase</p>
                </div>
                
                <div id="import-wallet-option" class="wallet-option @if($errors->import->any()) selected @endif">
                    <div class="bg-gray-100 dark:bg-gray-800 rounded-full p-3 mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 3v4a1 1 0 001 1h4" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 11v6m0 0l-3-3m3 3l3-3" />
                        </svg>
                    </div>
                    <h3 class="font-medium text-gray-900 dark:text-white">Import Existing Wallet</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Use a recovery phrase</p>
                </div>
            </div>
            
            <!-- Create Wallet Form -->
            <form id="create-wallet-form" 
                  method="POST" 
                  action="{{ route('wallet.profiles.store') }}" 
                  class="@if($errors->import->any()) hidden @endif space-y-6">
                @csrf
                <input type="hidden" name="method" value="create">
                
                <!-- Wallet Name Input -->
                <div>
                    <label for="name_create" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Wallet Name</label>
                    <input 
                        id="name_create" 
                        name="name" 
                        value="{{ old('name') }}"
                        required 
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-800 dark:text-white"
                    />
                    @if ($errors->has('name'))
                        <p class="mt-2 text-sm text-red-600">{{ $errors->first('name') }}</p>
                    @endif
                </div>
                
                <!-- Password Input -->
                <div>
                    <label for="password_create" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Your Password</label>
                    <input 
                        type="password"
                        id="password_create" 
                        name="password" 
                        required 
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-800 dark:text-white"
                    />
                    @if ($errors->has('password'))
                        <p class="mt-2 text-sm text-red-600">{{ $errors->first('password') }}</p>
                    @endif
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Enter your current password to authorize this action
                    </p>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="w-full justify-center inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:border-blue-700 focus:ring focus:ring-blue-300 disabled:opacity-25 transition">
                    Create Wallet
                </button>
            </form>
            
            <!-- Import Wallet Form -->
            <form id="import-wallet-form" 
                  method="POST" 
                  action="{{ route('wallet.profiles.store') }}" 
                  class="@if(!$errors->import->any()) hidden @endif space-y-6">
                @csrf
                <input type="hidden" name="method" value="import">
                
                <!-- Wallet Name Input -->
                <div>
                    <label for="name_import" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Wallet Name</label>
                    <input 
                        id="name_import" 
                        name="name" 
                        value="{{ old('name') }}"
                        required 
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-800 dark:text-white"
                    />
                    @if ($errors->import->has('name'))
                        <p class="mt-2 text-sm text-red-600">{{ $errors->import->first('name') }}</p>
                    @endif
                </div>
                
                <!-- Recovery Phrase -->
                <div>
                    <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">Recovery Phrase</label>
                    <select id="phrase_length" name="phrase_length" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-800 dark:text-white">
                        <option value="12" @selected(old('phrase_length', '12') == '12')>12 words</option>
                        <option value="18" @selected(old('phrase_length') == '18')>18 words</option>
                        <option value="24" @selected(old('phrase_length') == '24')>24 words</option>
                    </select>
                    
                    <textarea 
                        id="seed_phrase_input" 
                        name="seed_words" 
                        rows="3" 
                        class="mt-4 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-800 dark:text-white"
                        placeholder="Enter your recovery phrase (space-separated words)"
                    >{{ old('seed_words') }}</textarea>
                    
                    @if ($errors->import->has('seed_words'))
                        <p class="mt-2 text-sm text-red-600">{{ $errors->import->first('seed_words') }}</p>
                    @endif
                </div>
                
                <!-- Password Input -->
                <div>
                    <label for="password_import" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Your Password</label>
                    <input 
                        type="password"
                        id="password_import" 
                        name="password" 
                        required 
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-800 dark:text-white"
                    />
                    @if ($errors->import->has('password'))
                        <p class="mt-2 text-sm text-red-600">{{ $errors->import->first('password') }}</p>
                    @endif
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Enter your current password to authorize this action
                    </p>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="w-full justify-center inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:border-blue-700 focus:ring focus:ring-blue-300 disabled:opacity-25 transition">
                    Import Wallet
                </button>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const createWalletOption = document.getElementById('create-wallet-option');
    const importWalletOption = document.getElementById('import-wallet-option');
    const createWalletForm = document.getElementById('create-wallet-form');
    const importWalletForm = document.getElementById('import-wallet-form');
    const phraseLength = document.getElementById('phrase_length');
    const seedPhraseInput = document.getElementById('seed_phrase_input');

    // Toggle between create and import options
    function toggleWalletOptions(isImport) {
        if (isImport) {
            createWalletOption.classList.remove('selected');
            createWalletOption.classList.remove('bg-blue-100');
            createWalletOption.classList.add('bg-gray-100');
            
            importWalletOption.classList.add('selected');
            importWalletOption.classList.remove('bg-gray-100');
            importWalletOption.classList.add('bg-blue-100');
            
            createWalletForm.classList.add('hidden');
            importWalletForm.classList.remove('hidden');
        } else {
            importWalletOption.classList.remove('selected');
            importWalletOption.classList.remove('bg-blue-100');
            importWalletOption.classList.add('bg-gray-100');
            
            createWalletOption.classList.add('selected');
            createWalletOption.classList.remove('bg-gray-100');
            createWalletOption.classList.add('bg-blue-100');
            
            importWalletForm.classList.add('hidden');
            createWalletForm.classList.remove('hidden');
        }
    }

    // Make entire div clickable
    createWalletOption.addEventListener('click', () => toggleWalletOptions(false));
    importWalletOption.addEventListener('click', () => toggleWalletOptions(true));

    // Normalize seed phrase input
    // seedPhraseInput.addEventListener('input', function() {
    //     // Normalize input: remove extra spaces, convert to lowercase
    //     const words = this.value.trim().toLowerCase().split(/\s+/);
    //     this.value = words.join(' ');
    // });

    // Handle form errors
    @if($errors->import->any())
        toggleWalletOptions(true);
    @elseif($errors->create->any())
        toggleWalletOptions(false);
    @endif

    // Add hover effects
    [createWalletOption, importWalletOption].forEach(option => {
        option.addEventListener('mouseenter', () => {
            if (!option.classList.contains('selected')) {
                option.classList.add('hover:bg-blue-50');
            }
        });
        option.addEventListener('mouseleave', () => {
            option.classList.remove('hover:bg-blue-50');
        });
    });
});
</script>
@endsection