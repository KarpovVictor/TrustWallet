<!-- resources/views/auth/welcome.blade.php -->
@extends('layouts.app')

@section('content')
<div class="min-h-screen flex flex-col bg-blue-900 dark:bg-gray-900">
    <div class="flex-1 flex flex-col justify-center items-center p-6">
        <div class="w-full max-w-md mx-auto">
            <div class="text-center mb-8">
                <img src="{{ asset('images/logo.svg') }}" alt="Trust Wallet" class="h-20 mx-auto mb-4">
                <h1 class="text-2xl font-bold text-white mb-2">Welcome to Trust Wallet</h1>
                <p class="text-blue-200 dark:text-gray-400">The most trusted & secure crypto wallet</p>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl overflow-hidden">
                <a href="{{ route('create.wallet') }}" class="block p-4 border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <div class="flex items-center">
                        <div class="bg-blue-100 dark:bg-blue-900 rounded-full p-3 mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="font-semibold text-gray-900 dark:text-white">Create New Wallet</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Generate a new wallet and seed phrase</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-auto text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </a>
                
                <a href="{{ route('import.wallet') }}" class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <div class="flex items-center">
                        <div class="bg-blue-100 dark:bg-blue-900 rounded-full p-3 mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="font-semibold text-gray-900 dark:text-white">Import Existing Wallet</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Use a recovery phrase or private key</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-auto text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </a>
            </div>
            
            <p class="text-center text-sm text-blue-200 dark:text-gray-400 mt-6">
                By continuing, you agree to the <a href="#" class="underline">Terms of Services</a> and <a href="#" class="underline">Privacy Policy</a>.
            </p>
        </div>
    </div>
</div>
@endsection