<!-- resources/views/auth/create-wallet.blade.php -->
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
                <div class="h-1 w-1/3 bg-gray-700 rounded ml-2"></div>
            </div>
            
            <h1 class="text-2xl font-semibold text-white text-center mb-4">Создайте пароль</h1>
            
            <p class="text-gray-400 text-center mb-6">
                Этот пароль используется для защиты вашего кошелька и обеспечивает доступ к кошельку. Его нельзя сбросить, и он отделен от мобильного кошелька.
            </p>
            
            <form action="{{ route('seed.phrase') }}" method="GET">
                @csrf
                
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-400 mb-1">Новый пароль</label>
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
                
                <div class="grid grid-cols-1 gap-2 mb-4">
                    <div class="flex items-center">
                        <div id="check-length" class="w-5 h-5 rounded-full border border-gray-500 inline-flex items-center justify-center mr-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-green-500 hidden" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <span class="text-sm text-gray-400">8 или больше символов</span>
                    </div>
                    
                    <div class="flex items-center">
                        <div id="check-uppercase" class="w-5 h-5 rounded-full border border-gray-500 inline-flex items-center justify-center mr-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-green-500 hidden" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <span class="text-sm text-gray-400">Минимум одна заглавная буква</span>
                    </div>
                    
                    <div class="flex items-center">
                        <div id="check-number" class="w-5 h-5 rounded-full border border-gray-500 inline-flex items-center justify-center mr-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-green-500 hidden" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <span class="text-sm text-gray-400">Минимум одна цифра</span>
                    </div>
                    
                    <div class="flex items-center">
                        <div id="check-special" class="w-5 h-5 rounded-full border border-gray-500 inline-flex items-center justify-center mr-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-green-500 hidden" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <span class="text-sm text-gray-400">Минимум один специальный символ</span>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-400 mb-1">Подтвердите новый пароль</label>
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
                    <button type="submit" id="continueBtn" disabled class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-500 hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Продолжить
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
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('password_confirmation');
        const termsCheckbox = document.getElementById('terms');
        const continueBtn = document.getElementById('continueBtn');
        
        const checkLength = document.getElementById('check-length');
        const checkUppercase = document.getElementById('check-uppercase');
        const checkNumber = document.getElementById('check-number');
        const checkSpecial = document.getElementById('check-special');
        
        function validatePassword() {
            const password = passwordInput.value;
            
            // Check length
            if (password.length >= 8) {
                checkLength.classList.add('bg-green-500');
                checkLength.classList.remove('border-gray-500');
                checkLength.querySelector('svg').classList.remove('hidden');
            } else {
                checkLength.classList.remove('bg-green-500');
                checkLength.classList.add('border-gray-500');
                checkLength.querySelector('svg').classList.add('hidden');
            }
            
            // Check uppercase
            if (/[A-Z]/.test(password)) {
                checkUppercase.classList.add('bg-green-500');
                checkUppercase.classList.remove('border-gray-500');
                checkUppercase.querySelector('svg').classList.remove('hidden');
            } else {
                checkUppercase.classList.remove('bg-green-500');
                checkUppercase.classList.add('border-gray-500');
                checkUppercase.querySelector('svg').classList.add('hidden');
            }
            
            // Check number
            if (/[0-9]/.test(password)) {
                checkNumber.classList.add('bg-green-500');
                checkNumber.classList.remove('border-gray-500');
                checkNumber.querySelector('svg').classList.remove('hidden');
            } else {
                checkNumber.classList.remove('bg-green-500');
                checkNumber.classList.add('border-gray-500');
                checkNumber.querySelector('svg').classList.add('hidden');
            }
            
            // Check special character
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                checkSpecial.classList.add('bg-green-500');
                checkSpecial.classList.remove('border-gray-500');
                checkSpecial.querySelector('svg').classList.remove('hidden');
            } else {
                checkSpecial.classList.remove('bg-green-500');
                checkSpecial.classList.add('border-gray-500');
                checkSpecial.querySelector('svg').classList.add('hidden');
            }
            
            updateContinueButton();
        }
        
        function updateContinueButton() {
            const isPasswordValid = passwordInput.value.length >= 8 && 
                                   /[A-Z]/.test(passwordInput.value) && 
                                   /[0-9]/.test(passwordInput.value) && 
                                   /[!@#$%^&*(),.?":{}|<>]/.test(passwordInput.value);
            const passwordsMatch = passwordInput.value === confirmPasswordInput.value && passwordInput.value !== '';
            const termsAccepted = termsCheckbox.checked;
            
            continueBtn.disabled = !(isPasswordValid && passwordsMatch && termsAccepted);
            continueBtn.classList.toggle('opacity-50', !isPasswordValid || !passwordsMatch || !termsAccepted);
        }
        
        passwordInput.addEventListener('input', validatePassword);
        confirmPasswordInput.addEventListener('input', updateContinueButton);
        termsCheckbox.addEventListener('change', updateContinueButton);
    });
</script>
@endsection