<?php

namespace Lightworx\FilamentPwa;

use Illuminate\Support\ServiceProvider;


class FilamentPwaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../resources/public' => public_path(),
        ], 'filament-pwa-assets');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}