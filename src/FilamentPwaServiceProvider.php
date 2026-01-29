<?php

namespace Lightworx\FilamentPwa;

use Illuminate\Support\ServiceProvider;
use Lightworx\FilamentPwa\Console\InstallFilamentPwaCommand;

class FilamentPwaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../resources/public' => public_path(),
        ], 'filament-pwa-assets');

        $this->publishes([
            __DIR__.'/../resources/views/pwa' => resource_path('views/vendor/pwa'),
        ], 'filament-pwa-views');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        $this->publishes([
            __DIR__.'/../resources/public/service-worker.js' => public_path('service-worker.js'),
            __DIR__.'/../resources/public/manifest.json'     => public_path('manifest.json'),
            __DIR__.'/../resources/public/register.js'       => public_path('register.js'),
            __DIR__.'/../resources/public/pwa'               => public_path('pwa'),
        ], 'filament-pwa-assets');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallFilamentPwaCommand::class,
            ]);
        }
    }
}