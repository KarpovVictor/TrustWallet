<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class HttpsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        \URL::forceScheme('https');
        
        $this->app->bind('url', function ($app) {
            $url = new \Illuminate\Routing\UrlGenerator(
                $app['router']->getRoutes(),
                \Request::instance()
            );
            $url->forceScheme('https');
            return $url;
        });
    }
}
