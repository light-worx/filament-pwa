<?php

namespace Lightworx\FilamentPwa;

use Illuminate\Support\ServiceProvider;
use Lightworx\FilamentPwa\Console\InstallFilamentPwaCommand;

class FilamentPwaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // ── Views ─────────────────────────────────────────────────────────────
        // Register package views as fallback; published views in vendor/pwa take
        // precedence automatically because Laravel checks resource path first.
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'pwa');

        // ── Routes ────────────────────────────────────────────────────────────
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // ── Migrations ────────────────────────────────────────────────────────
        $this->loadMigrationsFrom(__DIR__ . '/Database/migrations');

        // ── Publishable: config ───────────────────────────────────────────────
        $this->publishes([
            __DIR__ . '/Config/pwa.php' => config_path('pwa.php'),
        ], 'filament-pwa-config');

        // ── Publishable: views ────────────────────────────────────────────────
        $this->publishes([
            __DIR__ . '/../resources/views/pwa' => resource_path('views/vendor/pwa'),
        ], 'filament-pwa-views');

        // ── Publishable: public assets ────────────────────────────────────────
        $this->publishes([
            __DIR__ . '/../resources/public/service-worker.js' => public_path('service-worker.js'),
            __DIR__ . '/../resources/public/pwa'               => public_path('pwa'),
        ], 'filament-pwa-assets');

        // ── Artisan commands ──────────────────────────────────────────────────
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallFilamentPwaCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/pwa.php', 'pwa');
    }
}