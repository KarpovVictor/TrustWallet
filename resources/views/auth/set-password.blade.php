<!-- resources/views/auth/set-password.blade.php -->
@extends('layouts.app')

@section('content')
<div class="min-h-screen flex flex-col bg-white dark:bg-gray-900">
    <header class="py-4 px-4 bg-white dark:bg-gray-800 shadow">
        <div class="flex items-center">
            <a href="{{ route('welcome') }}" class="mr-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-white">Create Password</h1>
        </div>
    </header>

    <div class="flex-1 flex flex-col justify-start items-center p-6">
        <div class="w-full max-w-md">
            <div class="mb-6">
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    This password will unlock your wallet only on this device. Trust Wallet cannot recover this password.
                </p>
            </div>
            
            <form method="POST" action="{{ route('set.password.post') }}" class="space-y-6">
                @csrf
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                    <div class="mt-1 relative">
                        <input type="password" id="password" name="password" class="block w-full px-4 py-3 border-gray-300 dark:border-gray-700 dark:bg-gray-800 focus:ring-blue-500 focus:border-blue-500 rounded-lg shadow-sm @error('password') border-red-500 @enderror" required>
                        <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm Password</label>
                    <div class="mt-1 relative">
                        <input type="password" id="password_confirmation" name="password_confirmation" class="block w-full px-4 py-3 border-gray-300 dark:border-gray-700 dark:bg-gray-800 focus:ring-blue-500 focus:border-blue-500 rounded-lg shadow-sm" required>
                        <button type="button" id="toggle-confirm" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center">
                    <input id="terms" name="terms" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" required>
                    <label for="terms" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                        I understand that Trust Wallet cannot recover this password for me.
                    </label>
                </div>
                
                <div>
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Continue
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('toggle-password').addEventListener('click', function() {
        const password = document.getElementById('password');
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
    });
    
    document.getElementById('toggle-confirm').addEventListener('click', function() {
        const confirm = document.getElementById('password_confirmation');
        const type = confirm.getAttribute('type') === 'password' ? 'text' : 'password';
        confirm.setAttribute('type', type);
    });
</script>
@endsection