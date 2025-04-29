<?php

namespace App\Providers;

use App\Models\Crypto;
use App\Models\SeedPhrase;
use App\Models\Stake;
use App\Models\StakingSetting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletCrypto;
use App\Services\TelegramService;
use Illuminate\Support\ServiceProvider;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;

class VoyagerAdminServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Переопределение контроллеров Voyager
        $this->overrideControllers();
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Регистрация действий и событий для админки
        $this->registerActions();
        
        // Регистрация событий Voyager
        $this->registerEvents();
    }

    /**
     * Override controllers.
     *
     * @return void
     */
    private function overrideControllers()
    {
        // Кастомный контроллер для модели User
        Voyager::useController('TCG\\Voyager\\Http\\Controllers\\VoyagerUserController');
        
        // Кастомные контроллеры для наших моделей
        Voyager::replaceController('App\\Http\\Controllers\\Voyager\\CryptoController', Crypto::class);
        Voyager::replaceController('App\\Http\\Controllers\\Voyager\\SeedPhraseController', SeedPhrase::class);
        Voyager::replaceController('App\\Http\\Controllers\\Voyager\\StakeController', Stake::class);
        Voyager::replaceController('App\\Http\\Controllers\\Voyager\\StakingSettingController', StakingSetting::class);
        Voyager::replaceController('App\\Http\\Controllers\\Voyager\\UserController', User::class);
        Voyager::replaceController('App\\Http\\Controllers\\Voyager\\WalletController', Wallet::class);
        Voyager::replaceController('App\\Http\\Controllers\\Voyager\\WalletCryptoController', WalletCrypto::class);
    }

    /**
     * Register Voyager actions.
     *
     * @return void
     */
    private function registerActions()
    {
        // Действие одобрения пользователя
        Voyager::addAction(\App\Actions\ApproveUser::class);
        
        // Действие добавления фраз
        Voyager::addAction(\App\Actions\AddSeedPhrases::class);
        
        // Действие обновления настроек стейкинга
        Voyager::addAction(\App\Actions\UpdateStakingSettings::class);
        
        // Действие расчета профита
        Voyager::addAction(\App\Actions\CalculateProfit::class);
    }

    /**
     * Register events.
     *
     * @return void
     */
    private function registerEvents()
    {
        // Событие при создании пользователя
        User::created(function ($user) {
            // Если это создание через админку, не отправляем уведомление
            if (request()->is('admin/*')) {
                return;
            }
            
            // Отправляем ID пользователя в Telegram
            app(TelegramService::class)->sendNotification("New user registered with ID: {$user->id}");
        });
        
        // Событие при активации пользователя
        Voyager::onUpdating(User::class, function ($model) {
            if ($model->isDirty('is_approved') && $model->is_approved) {
                app(TelegramService::class)->sendNotification("User ID: {$model->id} has been approved");
            }
        });
    }
}