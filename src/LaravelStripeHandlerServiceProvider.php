<?php


namespace Inaxo\LaravelStripeHandler;


use Illuminate\Support\ServiceProvider;

class LaravelStripeHandlerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/laravel_stripe_handler.php' => config_path('laravel_stripe_handler.php'),
        ], 'config');
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__.'/views', 'LaravelStripeHandler');
    }


    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/laravel_stripe_handler.php', 'laravel_stripe_handler');
    }
}
