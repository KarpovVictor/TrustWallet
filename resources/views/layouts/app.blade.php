<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Trust Wallet') }}</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
    
    <!-- Включаем тему -->
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <style>
        .profit-text {
            color: #008e29 !important;
        }

        /* Стили для анимации загрузки кнопок */
        .stake-loading, .unstake-loading {
            position: relative;
        }

        .stake-loading:disabled, .unstake-loading:disabled {
            cursor: wait;
        }

        /* Стили для кнопки "Unstake" */
        .unstake-button {
            transition: all 0.3s ease;
        }

        .unstake-button-locked {
            cursor: not-allowed;
            opacity: 0.7;
        }

        /* Стили для плавной анимации при появлении прибыли */
        .profit-animation {
            animation: fadeInGreen 1s ease-in-out;
        }

        @keyframes fadeInGreen {
            0% {
                opacity: 0;
                transform: translateY(5px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Кастомный спиннер для Tailwind */
        .loader {
            border-radius: 50%;
            width: 1em;
            height: 1em;
            border: 0.2em solid rgba(255,255,255,0.2);
            border-top-color: white;
            animation: spin 1s infinite linear;
            display: inline-block;
            vertical-align: middle;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        /* Анимация для кнопки Max */
        .btn-max:active {
            transform: scale(0.97);
        }
    </style>
</head>
<body class="font-sans antialiased h-full bg-gray-50 dark:bg-gray-900 dark:text-white">
    <div id="app" class="min-h-screen flex flex-col">
        <!-- Основное содержимое -->
        <main class="flex-1">
            @yield('content')
        </main>
        
        <!-- Навигация внизу экрана (если мы в дашборде) -->
        @auth
            @if(request()->routeIs('dashboard') || request()->routeIs('staking.*') || request()->routeIs('settings') || request()->routeIs('history') || request()->routeIs('support.*'))
                <nav class="fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 shadow-lg border-t border-gray-200 dark:border-gray-700">
                    <div class="flex justify-around py-2">
                        <a href="{{ route('dashboard') }}" class="flex flex-col items-center p-2 {{ request()->routeIs('dashboard') ? 'text-blue-500' : 'text-gray-600 dark:text-gray-400' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span class="text-xs">Home</span>
                        </a>
                        <a href="{{ route('staking.index') }}" class="flex flex-col items-center p-2 {{ request()->routeIs('staking.*') ? 'text-blue-500' : 'text-gray-600 dark:text-gray-400' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-xs">Earn</span>
                        </a>
                        <a href="{{ route('history') }}" class="flex flex-col items-center p-2 {{ request()->routeIs('history') ? 'text-blue-500' : 'text-gray-600 dark:text-gray-400' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-xs">History</span>
                        </a>
                        <a href="{{ route('settings') }}" class="flex flex-col items-center p-2 {{ request()->routeIs('settings') ? 'text-blue-500' : 'text-gray-600 dark:text-gray-400' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="text-xs">Settings</span>
                        </a>
                    </div>
                </nav>
            @endif
        @endauth
    </div>
    <script>
        /**
        * Скрипт для управления стейкингом и анимациями
        */
        document.addEventListener('DOMContentLoaded', function() {
            // Элементы форм
            const stakeForm = document.getElementById('stakeForm');
            const unstakeForm = document.getElementById('unstakeForm');
            const amountInput = document.getElementById('amount');
            const stakeButton = document.getElementById('stakeButton');
            const unstakeButton = document.getElementById('unstakeButton');
            const maxButton = document.querySelector('.btn-max');

            // Анимация загрузки для кнопки стейкинга
            if (stakeForm) {
                stakeForm.addEventListener('submit', function(e) {
                    if (this.checkValidity()) {
                        const button = this.querySelector('button[type="submit"]');
                        if (button) {
                            // Показываем загрузку на кнопке
                            const originalText = button.innerHTML;
                            button.innerHTML = '<span class="loader"></span> Обработка...';
                            button.disabled = true;

                            // Сохраняем оригинальный текст кнопки
                            button.dataset.originalText = originalText;
                            
                            // Добавляем класс для стилизации
                            button.classList.add('stake-loading');
                            
                            // Сохраняем форму, чтобы восстановить если что-то пойдет не так
                            setTimeout(() => {
                                if (button.disabled) {
                                    // Если кнопка всё еще отключена после 10 секунд, восстанавливаем её
                                    button.innerHTML = originalText;
                                    button.disabled = false;
                                    button.classList.remove('stake-loading');
                                }
                            }, 10000); // Таймаут на случай, если запрос не вернется
                        }
                    }
                });
            }

            // Анимация загрузки для кнопки unstake
            if (unstakeForm) {
                unstakeForm.addEventListener('submit', function(e) {
                    const button = this.querySelector('button[type="submit"]');
                    if (button) {
                        // Показываем загрузку на кнопке
                        const originalText = button.innerHTML;
                        button.innerHTML = '<span class="loader"></span> Обработка...';
                        button.disabled = true;
                        
                        // Сохраняем оригинальный текст кнопки
                        button.dataset.originalText = originalText;
                        
                        // Добавляем класс для стилизации
                        button.classList.add('unstake-loading');
                        
                        // Сохраняем форму, чтобы восстановить если что-то пойдет не так
                        setTimeout(() => {
                            if (button.disabled) {
                                // Если кнопка всё еще отключена после 10 секунд, восстанавливаем её
                                button.innerHTML = originalText;
                                button.disabled = false;
                                button.classList.remove('unstake-loading');
                            }
                        }, 10000); // Таймаут на случай, если запрос не вернется
                    }
                });
            }

            // Валидация ввода суммы стейкинга
            if (amountInput && stakeButton) {
                amountInput.addEventListener('input', function() {
                    validateStakeAmount();
                });
                
                // Инициализируем состояние кнопки при загрузке страницы
                validateStakeAmount();
            }

            // Функция для валидации суммы стейкинга
            function validateStakeAmount() {
                if (!amountInput || !stakeButton) return;
                
                const minAmount = parseFloat(amountInput.getAttribute('min') || 0);
                const maxAmount = parseFloat(amountInput.getAttribute('max') || 0);
                const value = parseFloat(amountInput.value);
                
                const errorElement = document.getElementById('amount-error');
                
                if (isNaN(value) || value < minAmount) {
                    stakeButton.disabled = true;
                    stakeButton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                    stakeButton.classList.add('bg-gray-300', 'text-gray-600', 'cursor-not-allowed');
                    
                    if (errorElement) {
                        if (isNaN(value) || value === 0) {
                            errorElement.textContent = 'Введите сумму для стейкинга';
                        } else {
                            errorElement.textContent = `Минимальная сумма: ${minAmount}`;
                        }
                        errorElement.classList.remove('hidden');
                    }
                } else if (value > maxAmount) {
                    stakeButton.disabled = true;
                    stakeButton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                    stakeButton.classList.add('bg-gray-300', 'text-gray-600', 'cursor-not-allowed');
                    
                    if (errorElement) {
                        errorElement.textContent = 'Недостаточно средств';
                        errorElement.classList.remove('hidden');
                    }
                } else {
                    stakeButton.disabled = false;
                    stakeButton.classList.remove('bg-gray-300', 'text-gray-600', 'cursor-not-allowed');
                    stakeButton.classList.add('bg-blue-600', 'text-white', 'hover:bg-blue-700');
                    
                    if (errorElement) {
                        errorElement.classList.add('hidden');
                    }
                }
            }

            // Кнопка "Max" для установки максимальной суммы
            if (maxButton && amountInput) {
                maxButton.addEventListener('click', function() {
                    const max = parseFloat(amountInput.getAttribute('max') || 0);
                    amountInput.value = max.toString();
                    validateStakeAmount();
                });
            }
        });
    </script>
</body>
</html>