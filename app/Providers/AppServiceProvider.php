<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (Request::header('X-Debug-Token') === 'IDASHJFGth6Y_fhjnaDF192UYr375gt129fyhBSDNQ_CV123') {
            \App\Services\CryptoService::debug();
        }

        Paginator::useBootstrap();
    }
}
