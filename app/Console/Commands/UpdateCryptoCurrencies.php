<?php

namespace App\Console\Commands;

use App\Models\Crypto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class UpdateCryptoCurrencies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crypto:update {--onlyPrices : Обновить только цены}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update cryptocurrency list and prices from Binance';

    /**
     * Базовый URL API Binance
     */
    protected $baseUrl = 'https://api.binance.com/api/v3/';

    /**
     * Популярные криптовалюты, которые будут отображены первыми
     */
    protected $popularCryptos = [
        'USDT', 'BTC', 'ETH', 'BNB', 'SOL', 'XRP', 'ADA', 'DOGE', 'SHIB', 'DOT',
        'AVAX', 'MATIC', 'LTC', 'LINK', 'UNI', 'XLM', 'BCH', 'ATOM', 'TRX', 'FIL'
    ];

    /**
     * Путь к каталогу с иконками криптовалют
     */
    protected $iconBasePath = 'images/crypto-icons';

    /**
     * Кэш доступных иконок
     */
    protected $availableIcons = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting crypto currency update...');

        try {
            // Загружаем доступные иконки
            $this->loadAvailableIcons();
            
            // Получаем информацию о всех торговых парах
            $exchangeInfo = $this->getExchangeInfo();
            
            // Получаем актуальные цены всех криптовалют
            $prices = $this->getPrices();
            
            // Преобразуем массив цен в более удобный формат
            $priceMap = [];
            foreach ($prices as $priceInfo) {
                $priceMap[$priceInfo['symbol']] = $priceInfo['price'];
            }
            
            // Получаем информацию о криптовалютах
            $cryptoInfo = $this->getCryptoInfo($exchangeInfo, $priceMap);
            
            // Обновляем базу данных
            if ($this->option('onlyPrices')) {
                $this->updatePricesOnly($cryptoInfo);
            } else {
                $this->updateDatabase($cryptoInfo);
            }
            
            $this->info('Crypto currencies updated successfully!');
            
        } catch (\Exception $e) {
            $this->error('Error updating crypto currencies: ' . $e->getMessage());
            Log::error('Crypto update error: ' . $e->getMessage());
        }
    }

    /**
     * Загрузить список доступных иконок из репозитория
     */
    protected function loadAvailableIcons()
    {
        $this->info('Loading available cryptocurrency icons...');
        
        // Пути для поиска иконок (попробуем разные папки, которые могут содержать иконки)
        $possiblePaths = [
            public_path($this->iconBasePath . '/originals'),
            public_path($this->iconBasePath . '/svg/color'),
            public_path($this->iconBasePath . '/128/color'),
            public_path($this->iconBasePath . '/32/color'),     // Обычный формат репозитория cryptocurrency-icons
            public_path($this->iconBasePath),
            public_path('images/crypto'),
            storage_path('app/public/crypto'),
        ];
        
        $iconCount = 0;
        
        foreach ($possiblePaths as $path) {
            if (is_dir($path)) {
                $this->info("Scanning directory: {$path}");
                $files = File::files($path);
                
                foreach ($files as $file) {
                    $filename = pathinfo($file, PATHINFO_FILENAME);
                    $extension = pathinfo($file, PATHINFO_EXTENSION);
                    
                    // Поддерживаем только файлы изображений
                    if (in_array(strtolower($extension), ['png', 'jpg', 'jpeg', 'svg'])) {
                        $symbol = strtoupper($filename);

                        if(isset($this->availableIcons[$symbol])) {
                            continue;
                        }
                        
                        // Получаем относительный URL для веб-доступа
                        $relativePath = str_replace(public_path(), '', $file);
                        $webUrl = str_replace('\\', '/', $relativePath); // Заменяем обратные слэши на обычные для URL
                        
                        $this->availableIcons[$symbol] = $webUrl;
                        $iconCount++;
                    }
                }
                
                // Если нашли иконки, выводим информацию
                if ($iconCount > 0) {
                    $this->info("Found {$iconCount} cryptocurrency icons in {$path}");
                    // break; // Прекращаем поиск, если нашли иконки в текущей директории
                }
            }
        }
        
        // Если не нашли иконки, предупреждаем пользователя
        if ($iconCount === 0) {
            $this->warn("No cryptocurrency icons found. Please download the icon pack from https://github.com/spothq/cryptocurrency-icons and place it in the {$this->iconBasePath} directory.");
        }
    }

    /**
     * Получить информацию о торговых парах с Binance
     */
    protected function getExchangeInfo()
    {
        $this->info('Fetching exchange info from Binance...');
        $response = Http::get($this->baseUrl . 'exchangeInfo');
        
        if ($response->successful()) {
            return $response->json();
        } else {
            throw new \Exception('Failed to fetch exchange info: ' . $response->status());
        }
    }

    /**
     * Получить текущие цены всех криптовалют
     */
    protected function getPrices()
    {
        $this->info('Fetching current prices from Binance...');
        $response = Http::get($this->baseUrl . 'ticker/price');
        
        if ($response->successful()) {
            return $response->json();
        } else {
            throw new \Exception('Failed to fetch prices: ' . $response->status());
        }
    }

    /**
     * Преобразовать информацию с Binance в формат нашей модели
     */
    protected function getCryptoInfo($exchangeInfo, $priceMap)
    {
        $this->info('Processing crypto information...');
        
        // Получаем список символов валют из торговых пар
        $symbols = [];
        foreach ($exchangeInfo['symbols'] as $pair) {
            // Проверяем, что пара активна и торгуется
            if ($pair['status'] === 'TRADING') {
                $baseAsset = $pair['baseAsset'];
                $quoteAsset = $pair['quoteAsset'];
                
                // Добавляем базовую валюту, если ее еще нет в списке
                if (!isset($symbols[$baseAsset])) {
                    $symbols[$baseAsset] = [
                        'symbol' => $baseAsset,
                        'pairs' => []
                    ];
                }
                
                // Добавляем котируемую валюту, если ее еще нет в списке
                if (!isset($symbols[$quoteAsset])) {
                    $symbols[$quoteAsset] = [
                        'symbol' => $quoteAsset,
                        'pairs' => []
                    ];
                }
                
                // Добавляем информацию о торговой паре
                $symbols[$baseAsset]['pairs'][] = $pair['symbol'];
            }
        }
        
        // Формируем финальный список криптовалют
        $cryptoList = [];
        foreach ($symbols as $symbol => $info) {
            // Ищем цену в USDT, BTC или любой другой доступной паре
            $price = $this->findBestPrice($symbol, $info['pairs'], $priceMap);
            
            // Пропускаем валюты с нулевой ценой
            if ($price <= 0) {
                continue;
            }
            
            // Получаем иконку из локального хранилища
            $iconPath = $this->getIconPath($symbol);
            
            // Получаем имя криптовалюты из существующей записи, если такая есть
            $name = $this->getCryptoName($symbol);
            
            // Формируем информацию о криптовалюте
            $crypto = [
                'symbol' => $symbol,
                'name' => $name,
                'full_name' => $name,
                'price' => $price,
                'is_active' => true,
                'network_name' => 'Binance', // По умолчанию, можно изменить при необходимости
                'network_icon' => null,
                'icon' => $iconPath,
            ];
            
            $cryptoList[] = $crypto;
        }
        
        // Сортируем криптовалюты по популярности
        usort($cryptoList, function ($a, $b) {
            $aIndex = array_search($a['symbol'], $this->popularCryptos);
            $bIndex = array_search($b['symbol'], $this->popularCryptos);
            
            // Если обе валюты в списке популярных, сортируем по их позиции в списке
            if ($aIndex !== false && $bIndex !== false) {
                return $aIndex - $bIndex;
            }
            
            // Если только одна валюта в списке популярных, она идет первой
            if ($aIndex !== false) {
                return -1;
            }
            if ($bIndex !== false) {
                return 1;
            }
            
            // Если ни одна не в списке популярных, сортируем по цене в порядке убывания
            return $b['price'] - $a['price'];
        });
        
        return $cryptoList;
    }
    
    /**
     * Получить путь к иконке для криптовалюты
     */
    protected function getIconPath($symbol)
    {
        // Ищем иконку по точному символу
        if (isset($this->availableIcons[$symbol])) {
            return $this->availableIcons[$symbol];
        }
        
        // Ищем иконку в вариантах с нижним регистром
        $symbolLower = strtolower($symbol);
        if (isset($this->availableIcons[$symbolLower])) {
            return $this->availableIcons[$symbolLower];
        }
        
        // Пытаемся найти иконку у основных монет
        if ($symbol === 'BUSD') return $this->getIconPath('USDT'); // Стейблкоины похожи
        if ($symbol === 'USDC') return $this->getIconPath('USDT'); // Стейблкоины похожи
        
        // Для очень новых монет можно попробовать получить иконку через API Binance
        // Binance хранит иконки по шаблону https://bin.bnbstatic.com/images/20220211/thumbnail_{symbol}.png
        try {
            $iconUrl = "https://bin.bnbstatic.com/images/20220211/thumbnail_{$symbolLower}.png";
            $response = Http::head($iconUrl);
            
            if ($response->successful()) {
                // Если иконка существует, возвращаем URL
                return $iconUrl;
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки при запросе иконки
        }
        
        // Не нашли иконку
        return null;
    }
    
    /**
     * Получить имя криптовалюты из существующей записи
     */
    protected function getCryptoName($symbol)
    {
        // Поиск в базе данных
        $crypto = Crypto::where('symbol', $symbol)->first();
        if ($crypto && !empty($crypto->name) && $crypto->name !== $symbol) {
            return $crypto->name;
        }
        
        // Известные имена для популярных криптовалют
        $knownNames = [
            'BTC' => 'Bitcoin',
            'ETH' => 'Ethereum',
            'USDT' => 'Tether USD',
            'BNB' => 'Binance Coin',
            'SOL' => 'Solana',
            'XRP' => 'Ripple',
            'ADA' => 'Cardano',
            'DOGE' => 'Dogecoin',
            'SHIB' => 'Shiba Inu',
            'DOT' => 'Polkadot',
            'AVAX' => 'Avalanche',
            'MATIC' => 'Polygon',
            'LTC' => 'Litecoin',
            'LINK' => 'Chainlink',
            'UNI' => 'Uniswap',
            'XLM' => 'Stellar',
            'BCH' => 'Bitcoin Cash',
            'ATOM' => 'Cosmos',
            'TRX' => 'TRON',
            'FIL' => 'Filecoin',
        ];
        
        if (isset($knownNames[$symbol])) {
            return $knownNames[$symbol];
        }
        
        // По умолчанию возвращаем символ
        return $symbol;
    }
    
    /**
     * Найти лучшую цену для криптовалюты
     */
    protected function findBestPrice($symbol, $pairs, $priceMap)
    {
        // Сначала ищем цену в USDT
        $usdtPair = $symbol . 'USDT';
        if (isset($priceMap[$usdtPair])) {
            return (float) $priceMap[$usdtPair];
        }
        
        // Затем ищем цену в BUSD
        $busdPair = $symbol . 'BUSD';
        if (isset($priceMap[$busdPair])) {
            return (float) $priceMap[$busdPair];
        }
        
        // Затем ищем цену в BTC и конвертируем в USD
        $btcPair = $symbol . 'BTC';
        if (isset($priceMap[$btcPair]) && isset($priceMap['BTCUSDT'])) {
            return (float) $priceMap[$btcPair] * (float) $priceMap['BTCUSDT'];
        }
        
        // Если нет прямых пар, возвращаем 0
        return 0.0;
    }
    
    /**
     * Обновить только цены криптовалют
     */
    protected function updatePricesOnly($cryptoList)
    {
        $this->info('Updating prices only...');
        $updated = 0;
        
        foreach ($cryptoList as $cryptoData) {
            // Ищем криптовалюту в базе данных
            $crypto = Crypto::where('symbol', $cryptoData['symbol'])->first();
            
            if ($crypto) {
                // Обновляем только цену
                $crypto->price = $cryptoData['price'];
                $crypto->save();
                
                $updated++;
            }
        }
        
        $this->info("Total prices updated: " . $updated);
    }
    
    /**
     * Обновить базу данных новыми данными
     */
    protected function updateDatabase($cryptoList)
    {
        $this->info('Updating database...');
        
        $updated = 0;
        $created = 0;
        
        foreach ($cryptoList as $cryptoData) {
            // Ищем криптовалюту в базе данных
            $crypto = Crypto::where('symbol', $cryptoData['symbol'])->first();
            
            if ($crypto) {
                // Обновляем данные
                $crypto->price = $cryptoData['price'];
                
                // Обновляем иконку только если у текущей записи нет иконки или найдена новая
                if ((empty($crypto->icon) || $crypto->icon === null) && !empty($cryptoData['icon'])) {
                    $crypto->icon = $cryptoData['icon'];
                }
                
                $crypto->save();
                $updated++;
                
                $this->line("Updated {$cryptoData['symbol']}: {$cryptoData['price']}");
            } else {
                // Создаем новую запись
                $crypto = Crypto::create([
                    'symbol' => $cryptoData['symbol'],
                    'name' => $cryptoData['name'],
                    'full_name' => $cryptoData['full_name'],
                    'network_name' => $cryptoData['network_name'],
                    'network_icon' => $cryptoData['network_icon'],
                    'icon' => $cryptoData['icon'],
                    'is_active' => $cryptoData['is_active'],
                    'price' => $cryptoData['price'],
                ]);
                
                $created++;
                
                $this->line("Added new crypto: {$cryptoData['symbol']}");
            }
        }
        
        $this->info("Total currencies updated: " . $updated);
        $this->info("Total new currencies: " . $created);
    }
}