<?php

namespace Enlightn\EnlightnPro;

use Enlightn\Enlightn\PHPStan;
use Illuminate\Support\ServiceProvider;

class EnlightnProServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/enlightn.php' => config_path('enlightn.php'),
            ], 'enlightnpro');
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->resolving(PHPStan::class, function ($PHPStan) {
            $PHPStan->setConfigPath(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'phpstan.neon');
        });
    }
}
