<!-- resources/views/auth/import-wallet.blade.php -->
@extends('layouts.app')

@section('content')
<div class="min-h-screen flex flex-col justify-center items-center bg-gray-900">
    <div class="w-full max-w-md mx-auto">
        <div class="text-center mb-8">
            <img src="{{ asset('images/logo.svg') }}" alt="Trust Wallet" class="h-16 mx-auto mb-4">
        </div>
        
        <div class="bg-gray-800 rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-semibold text-white text-center mb-4">Импортировать, используя секретную фразу</h1>
            
            <form action="{{ route('import.wallet.post') }}" method="POST">
                @csrf
                
                <div class="mb-4">
                    <textarea id="seed_phrase" name="seed_phrase" rows="3" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Напишите свою секретную фразу" required></textarea>
                    
                    @error('seed_phrase')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div id="seed-words-container" class="grid grid-cols-3 gap-2 mb-6 hidden">
                    <!-- JS will populate this with input fields for each seed word -->
                </div>
                
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-400 mb-1">Пароль</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        <button type="button" onclick="togglePasswordVisibility('password')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-400 mb-1">Подтвердите пароль</label>
                    <div class="relative">
                        <input type="password" id="password_confirmation" name="password_confirmation" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        <button type="button" onclick="togglePasswordVisibility('password_confirmation')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="flex items-center mb-6">
                    <input type="checkbox" id="terms" name="terms" class="w-4 h-4 text-green-500 border-gray-600 rounded bg-gray-700 focus:ring-0" required>
                    <label for="terms" class="ml-2 text-sm text-gray-400">
                        Я прочитал и согласен с <a href="#" class="text-green-500">Правилами сервиса</a>.
                    </label>
                </div>
                
                <div class="flex justify-between">
                    <a href="{{ route('welcome') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-green-500 hover:text-green-400 focus:outline-none">
                        Назад
                    </a>
                    <button type="button" id="nextBtn" class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Дальше
                    </button>
                    <button type="submit" id="importBtn" class="hidden inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-500 hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Импортировать
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function togglePasswordVisibility(inputId) {
        const input = document.getElementById(inputId);
        input.type = input.type === 'password' ? 'text' : 'password';
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const seedPhraseInput = document.getElementById('seed_phrase');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('password_confirmation');
        const termsCheckbox = document.getElementById('terms');
        const nextBtn = document.getElementById('nextBtn');
        const importBtn = document.getElementById('importBtn');
        const seedWordsContainer = document.getElementById('seed-words-container');
        
        nextBtn.addEventListener('click', function() {
            const seedPhrase = seedPhraseInput.value.trim();
            const words = seedPhrase.split(/\s+/);
            
            if (words.length < 12) {
                alert('Пожалуйста, введите фразу из не менее 12 слов');
                return;
            }
            
            // Create hidden inputs for each seed word
            seedWordsContainer.innerHTML = '';
            words.forEach((word, index) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'seed_words[]';
                input.value = word;
                seedWordsContainer.appendChild(input);
            });
            
            // Show the password fields and the import button
            nextBtn.classList.add('hidden');
            importBtn.classList.remove('hidden');
        });
        
        // Validate password requirements (similar to create-wallet page)
        function validateForm() {
            const passwordsMatch = passwordInput.value === confirmPasswordInput.value && passwordInput.value !== '';
            const termsAccepted = termsCheckbox.checked;
            const seedPhraseValid = seedPhraseInput.value.trim().split(/\s+/).length >= 12;
            
            importBtn.disabled = !(passwordsMatch && termsAccepted && seedPhraseValid);
        }
        
        seedPhraseInput.addEventListener('input', validateForm);
        passwordInput.addEventListener('input', validateForm);
        confirmPasswordInput.addEventListener('input', validateForm);
        termsCheckbox.addEventListener('change', validateForm);
    });
</script>
@endsection