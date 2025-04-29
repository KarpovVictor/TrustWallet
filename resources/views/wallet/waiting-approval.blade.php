<!-- resources/views/wallet/waiting-approval.blade.php -->
@extends('layouts.app')

@section('content')
<div class="min-h-screen flex flex-col bg-white dark:bg-gray-900">
    <div class="flex-1 flex flex-col justify-center items-center p-6">
        <div class="w-full max-w-md mx-auto text-center">
            <div class="bg-yellow-100 dark:bg-yellow-900 rounded-full h-24 w-24 flex items-center justify-center mx-auto mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-yellow-500 dark:text-yellow-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Wallet Waiting for Approval</h1>
            
            <p class="text-gray-600 dark:text-gray-400 mb-8">
                Your wallet is being verified. This process may take some time. Please wait for administrator approval.
            </p>
            
            <div class="flex justify-center">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Sign Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Checking approval status every 30 seconds
    setInterval(function() {
        fetch('{{ route('check.approval.status') }}')
            .then(response => response.json())
            .then(data => {
                if (data.is_approved) {
                    window.location.href = '{{ route('dashboard') }}';
                }
            });
    }, 30000);
</script>
@endsection