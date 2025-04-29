<!-- resources/views/wallet/exchange.blade.php -->
@extends('layouts.app')

@section('content')
@if ($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 pb-20">
    <!-- Header -->
    <header class="bg-blue-600 dark:bg-blue-900 pb-4">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
            <div class="flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h2 class="text-xl font-bold text-white">Обмен валют</h2>
                <div class="w-6"></div>
            </div>
        </div>
    </header>

    <!-- Exchange Form -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <form action="{{ route('wallet.exchange.process') }}" method="POST" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            @csrf
            
            <!-- From Crypto Selection -->
            <div class="mb-4">
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Отдаете</label>
                <div class="grid grid-cols-2 gap-4">
                    <select name="from_crypto_id" id="from_crypto" class="w-full bg-gray-100 dark:bg-gray-700 rounded-lg p-3">
                        @foreach($walletCryptos as $walletCrypto)
                            <option 
                                value="{{ $walletCrypto->crypto->id }}" 
                                data-balance="{{ $walletCrypto->balance }}"
                                data-symbol="{{ $walletCrypto->crypto->symbol }}"
                                data-price="{{ $walletCrypto->crypto->price }}"
                            >
                                {{ $walletCrypto->crypto->name }} ({{ number_format($walletCrypto->balance, 4) }} {{ $walletCrypto->crypto->symbol }})
                            </option>
                        @endforeach
                    </select>
                    
                    <input 
                        type="number" 
                        name="amount" 
                        id="exchange_amount" 
                        placeholder="Сумма" 
                        step="0.00000001" 
                        min="0" 
                        class="w-full bg-gray-100 dark:bg-gray-700 rounded-lg p-3"
                    >
                </div>
                <p id="balance_info" class="text-sm text-gray-500 mt-1"></p>
            </div>

            {{-- <!-- Swap Icon -->
            <div class="flex justify-center my-4">
                <button type="button" id="swap_cryptos" class="bg-blue-500 text-white rounded-full p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                    </svg>
                </button>
            </div> --}}

            <!-- To Crypto Selection -->
            <div class="mb-4">
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Получаете</label>
                <select name="to_crypto_id" id="to_crypto" class="w-full bg-gray-100 dark:bg-gray-700 rounded-lg p-3">
                    @foreach($availableCryptos as $crypto)
                        <option 
                            value="{{ $crypto->id }}" 
                            data-symbol="{{ $crypto->symbol }}"
                            data-price="{{ $crypto->price }}"
                        >
                            {{ $crypto->name }} (${{ number_format($crypto->price, 2) }})
                        </option>
                    @endforeach
                </select>
                <p id="receive_info" class="text-sm text-gray-500 mt-1"></p>
            </div>

            <!-- Exchange Rate Info -->
            <div class="mb-4 bg-gray-100 dark:bg-gray-700 rounded-lg p-3 text-center">
                <p id="exchange_rate_info" class="text-sm text-gray-600 dark:text-gray-300"></p>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="w-full bg-blue-600 text-white rounded-lg p-3 mt-4 hover:bg-blue-700 transition">
                Обменять
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fromCryptoSelect = document.getElementById('from_crypto');
    const toCryptoSelect = document.getElementById('to_crypto');
    const exchangeAmountInput = document.getElementById('exchange_amount');
    const balanceInfo = document.getElementById('balance_info');
    const receiveInfo = document.getElementById('receive_info');
    const exchangeRateInfo = document.getElementById('exchange_rate_info');
    const swapButton = document.getElementById('swap_cryptos');

    function updateExchangeRate() {
        const fromOption = fromCryptoSelect.options[fromCryptoSelect.selectedIndex];
        const toOption = toCryptoSelect.options[toCryptoSelect.selectedIndex];
        
        const fromPrice = parseFloat(fromOption.dataset.price);
        const toPrice = parseFloat(toOption.dataset.price);
        const fromSymbol = fromOption.dataset.symbol;
        const toSymbol = toOption.dataset.symbol;
        const fromBalance = parseFloat(fromOption.dataset.balance);

        balanceInfo.textContent = `Доступно: ${fromBalance.toFixed(4)} ${fromSymbol}`;
        
        // Предел ввода суммы
        exchangeAmountInput.max = fromBalance;

        const conversionRate = fromPrice / toPrice;
        exchangeRateInfo.textContent = `Курс: 1 ${fromSymbol} = ${conversionRate.toFixed(4)} ${toSymbol}`;

        // Обработчик изменения суммы
        function updateReceiveAmount() {
            const amount = parseFloat(exchangeAmountInput.value) || 0;
            const fromAmountUsd = amount * fromPrice;
            const receivedAmount = fromAmountUsd / toPrice;
            
            receiveInfo.textContent = `Получите: ${receivedAmount.toFixed(4)} ${toSymbol}`;
        }

        exchangeAmountInput.addEventListener('input', updateReceiveAmount);
        updateReceiveAmount();
    }

    // Начальный расчет
    updateExchangeRate();

    // Обработка смены криптовалют
    fromCryptoSelect.addEventListener('change', updateExchangeRate);
    toCryptoSelect.addEventListener('change', updateExchangeRate);

    // Кнопка swap
    swapButton.addEventListener('click', function() {
        const fromIndex = fromCryptoSelect.selectedIndex;
        const toIndex = toCryptoSelect.selectedIndex;
        
        fromCryptoSelect.selectedIndex = toIndex;
        toCryptoSelect.selectedIndex = fromIndex;
        
        updateExchangeRate();
    });
});
</script>
@endsection