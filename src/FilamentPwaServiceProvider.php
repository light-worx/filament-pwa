<?php

namespace Lightworx\FilamentPwa;

use Illuminate\Support\ServiceProvider;
use Lightworx\FilamentPwa\Console\DownloadFlagsCommand;
use Lightworx\FilamentPwa\Console\InstallFilamentPwaCommand;
use Illuminate\Routing\Router;
use Lightworx\FilamentPwa\FieldOptions\FieldOptionsRegistry;
use Lightworx\FilamentPwa\Http\Middleware\PwaDeviceMiddleware;
use Lightworx\FilamentPwa\Services\PushNotificationService;

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

        // ── Named middleware alias ────────────────────────────────────────────────
        // Developers add 'pwa.device' to their web routes or HTTP kernel to get
        // $pwaPreference, $pwaCircuitId, $pwaPhone in controllers and views.
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('pwa.device', PwaDeviceMiddleware::class);

        // ── Artisan commands ──────────────────────────────────────────────────
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallFilamentPwaCommand::class,
                DownloadFlagsCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/pwa.php', 'pwa');

        // Register PushNotificationService as a singleton so the WebPush
        // client (and its HTTP connection pool) is reused within a request.
        $this->app->singleton(PushNotificationService::class);

        // FieldOptionsRegistry is a singleton so resolvers registered in
        // AppServiceProvider::boot() are available throughout the request.
        $this->app->singleton(FieldOptionsRegistry::class);
        $this->app->alias(FieldOptionsRegistry::class, 'pwa.field-options');
    }
}