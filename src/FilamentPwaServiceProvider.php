<?php

namespace Lightworx\FilamentPwa;

use Illuminate\Support\ServiceProvider;
use Lightworx\FilamentPwa\Console\InstallFilamentPwaCommand;
use Lightworx\FilamentPwa\Livewire\PwaUserSettings;
use Livewire\Livewire;

class FilamentPwaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../resources/public' => public_path(),
        ], 'filament-pwa-assets');
        $this->publishes([
            __DIR__.'/Config/pwa.php' => config_path('pwa.php'),
        ]);
        $this->publishes([
            __DIR__.'/../resources/views/pwa' => resource_path('views/vendor/pwa'),
        ], 'filament-pwa');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        $this->publishes([
            __DIR__.'/../resources/public/service-worker.js' => public_path('service-worker.js'),
            __DIR__.'/../resources/public/register.js'       => public_path('register.js'),
            __DIR__.'/../resources/public/pwa'               => public_path('pwa'),
        ], 'filament-pwa-assets');
        $this->loadMigrationsFrom(__DIR__.'/Database/migrations');
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallFilamentPwaCommand::class,
            ]);
        }
        $this->loadViewsFrom(__DIR__.'/../resources/views/pwa', 'filament-pwa');
        Livewire::component('pwa-user-settings', PwaUserSettings::class);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/pwa.php',  'pwa');
    }
}