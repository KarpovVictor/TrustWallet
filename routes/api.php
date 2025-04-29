<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WalletProfileController;
use App\Http\Controllers\Api\WalletExchangeController;
use App\Http\Controllers\Api\StakingController;
use App\Http\Controllers\Api\SupportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('auth')->group(function () {
    Route::post('/create-wallet', [AuthController::class, 'createWallet']);
    Route::post('/import-wallet', [AuthController::class, 'importWallet']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
});

Route::middleware('auth:api')->group(function () {
    Route::get('/check-approval-status', [WalletProfileController::class, 'checkApprovalStatus']);
    
    Route::middleware('approved')->group(function () {
        Route::prefix('wallet')->group(function () {
            Route::get('/dashboard', [WalletController::class, 'dashboard']);
            Route::get('/history', [WalletController::class, 'history']);
            
            Route::prefix('crypto')->group(function () {
                Route::get('/send-list', [WalletController::class, 'sendList']);
                Route::get('/receive-list', [WalletController::class, 'receiveList']);
                Route::get('/send/{symbol}', [WalletController::class, 'sendCrypto']);
                Route::post('/send/{symbol}', [WalletController::class, 'processSend']);
                Route::get('/receive/{symbol}', [WalletController::class, 'receiveCrypto']);
            });
            
            Route::prefix('exchange')->group(function () {
                Route::get('/', [WalletExchangeController::class, 'exchangeView']);
                Route::post('/', [WalletExchangeController::class, 'processExchange']);
            });
            
            Route::prefix('profiles')->group(function () {
                Route::get('/', [WalletProfileController::class, 'index']);
                Route::post('/', [WalletProfileController::class, 'store']);
                Route::post('/{id}/set-default', [WalletProfileController::class, 'setDefault']);
            });
            
            Route::prefix('settings')->group(function () {
                Route::get('/', [WalletController::class, 'settings']);
                Route::post('/theme', [WalletController::class, 'updateTheme']);
            });
        });
        
        Route::prefix('staking')->group(function () {
            Route::get('/', [StakingController::class, 'index']);
            Route::get('/{symbol}', [StakingController::class, 'showCrypto']);
            Route::post('/{symbol}/stake', [StakingController::class, 'stake']);
            Route::post('/unstake/{id}', [StakingController::class, 'unstake']);
        });
        
        Route::prefix('support')->group(function () {
            Route::get('/chat', [SupportController::class, 'index']);
            Route::post('/message', [SupportController::class, 'sendMessage']);
            Route::get('/ticket/{id}/messages', [SupportController::class, 'getMessages']);
            Route::post('/ticket/{id}/close', [SupportController::class, 'closeTicket']);
        });
    });
});

Route::post('/telegram/webhook', [SupportController::class, 'receiveAdminMessage']);