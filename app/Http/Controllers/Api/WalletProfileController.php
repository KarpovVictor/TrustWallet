<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Services\CryptoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WalletProfileController extends Controller
{
    protected $cryptoService;

    public function __construct(CryptoService $cryptoService)
    {
        $this->cryptoService = $cryptoService;
    }

    public function index()
    {
        $wallets = Auth::user()->wallets;
        
        return response()->json([
            'success' => true,
            'data' => $wallets
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:wallets,name,NULL,id,user_id,' . Auth::id(),
            'password' => 'required|current_password',
            'method' => 'required|in:create,import',
            'seed_words' => 'required_if:method,import|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            if ($request->method === 'create') {
                $seedPhrase = $this->cryptoService->generateSeedPhrase(12);
            } else {
                $seedPhrase = $request->seed_words;
                
                if (!$this->cryptoService->validateSeedPhrase($seedPhrase)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid seed phrase'
                    ], 422);
                }
            }

            $encryptedSeedPhrase = $seedPhrase;
            
            if(is_array($encryptedSeedPhrase)) {
                $encryptedSeedPhrase = implode(" ", $encryptedSeedPhrase);
            }

            $wallet = Wallet::create([
                'user_id' => Auth::id(),
                'name' => $request->name,
                'encrypted_seed_phrase' => $encryptedSeedPhrase,
                'is_default' => !Auth::user()->wallets()->exists(),
                'is_approved' => true
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $wallet,
                'message' => 'Wallet successfully created/imported'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error creating/importing wallet: ' . $e->getMessage()
            ], 500);
        }
    }

    public function setDefault($id)
    {
        $wallet = Wallet::findOrFail($id);

        if ($wallet->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient rights'
            ], 403);
        }

        Wallet::where('user_id', Auth::id())->update(['is_default' => false]);

        $wallet->update(['is_default' => true]);

        return response()->json([
            'success' => true,
            'data' => $wallet,
            'message' => 'Wallet is set by default'
        ]);
    }

    public function checkApprovalStatus()
    {
        $user = Auth::user();
        
        return response()->json([
            'success' => true,
            'status' => $user->is_approved ? 'approved' : 'pending'
        ]);
    }
}