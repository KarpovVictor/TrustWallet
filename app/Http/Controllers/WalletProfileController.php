<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Services\CryptoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class WalletProfileController extends Controller
{
    protected $cryptoService;

    public function __construct(CryptoService $cryptoService)
    {
        $this->middleware('auth')->except(['waitingApproval']);
        $this->cryptoService = $cryptoService;
    }

    public function index()
    {
        $wallets = Auth::user()->wallets;
        return view('wallet.profiles.index', compact('wallets'));
    }

    public function create()
    {
        return view('wallet.profiles.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:wallets,name,NULL,id,user_id,' . Auth::id(),
            'password' => 'required|current_password',
            'method' => 'required|in:create,import'
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return response()->json([
                'success' => false,
                'message' => $firstError
            ]);
        }

        try {
            DB::beginTransaction();

            // Генерируем или импортируем seed phrase
            if ($request->method === 'create') {
                $seedPhrase = $this->cryptoService->generateSeedPhrase(12);
            } else {
                $seedPhrase = $request->seed_words;
                
                if (!$this->cryptoService->validateSeedPhrase($seedPhrase)) {
                    return redirect()->back()->withErrors(['seed_words' => 'Недействительная seed-фраза'])->withInput();
                }
            }

            // Создаем новый кошелек
            $wallet = Wallet::create([
                'user_id' => Auth::id(),
                'name' => $request->name,
                'encrypted_seed_phrase' => $this->cryptoService->encryptSeedPhrase(
                    $seedPhrase, 
                    Auth::user()->password
                ),
                'is_default' => !Auth::user()->wallets()->exists(),
                'is_approved' => true
            ]);

            DB::commit();

            return redirect()->route('wallet.profiles.index')
                ->with('success', 'Кошелек успешно создан/импортирован');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Ошибка при создании/импорте кошелька: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function setDefault($id)
    {
        $wallet = Wallet::findOrFail($id);

        // Проверяем, что кошелек принадлежит текущему пользователю
        if ($wallet->user_id !== Auth::id()) {
            return redirect()->back()->withErrors(['error' => 'Недостаточно прав']);
        }

        // Сбрасываем текущий дефолтный кошелек
        Wallet::where('user_id', Auth::id())->update(['is_default' => false]);

        // Устанавливаем новый дефолтный кошелек
        $wallet->update(['is_default' => true]);

        return redirect()->route('wallet.profiles.index')
            ->with('success', 'Кошелек установлен по умолчанию');
    }

    public function waitingApproval()
    {
        if(auth()->user()?->is_approved == 1) {
            return redirect()->route('dashboard');
        }

        return view('wallet.waiting-approval');
    }

    public function checkApprovalStatus()
    {
        $user = Auth::user();
        
        if ($user->is_approved) {
            return response()->json(['status' => 'approved']);
        }
        
        return response()->json(['status' => 'pending']);
    }
}