<?php

namespace Lightworx\FilamentPwa\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class InstallFilamentPwaCommand extends Command
{
    protected $signature   = 'filament-pwa:install';
    protected $description = 'Install the Filament PWA package';

    public function handle(): int
    {
        $this->info('Installing Filament PWA...');

        // ── Prerequisite checks ───────────────────────────────────────────────
        if (!class_exists(\NotificationChannels\WebPush\WebPushServiceProvider::class)) {
            $this->error('laravel-webpush is not installed.');
            $this->line('  Run: composer require laravel-notification-channels/webpush');
            return self::FAILURE;
        }

        if (!class_exists(\Livewire\LivewireServiceProvider::class)) {
            $this->warn('Livewire does not appear to be installed.');
            $this->line('  Run: composer require livewire/livewire');
            // Non-fatal; some layouts may not use Livewire.
        }

        if (!Schema::hasTable('push_subscriptions')) {
            $this->error('The push_subscriptions table does not exist.');
            $this->line('  Please run the laravel-webpush migrations first:');
            $this->line('  php artisan migrate');
            return self::FAILURE;
        }

        // ── Publish assets ────────────────────────────────────────────────────
        $this->callSilently('vendor:publish', [
            '--tag'   => 'filament-pwa-assets',
            '--force' => true,
        ]);
        $this->info('  ✓ Assets published');

        // ── Publish config (skip if already exists) ───────────────────────────
        if (!file_exists(config_path('pwa.php'))) {
            $this->callSilently('vendor:publish', ['--tag' => 'filament-pwa-config']);
            $this->info('  ✓ Config published to config/pwa.php');
        } else {
            $this->line('  - config/pwa.php already exists, skipping');
        }

        // ── Run package migrations ────────────────────────────────────────────
        $this->callSilently('migrate');
        $this->info('  ✓ Migrations run');

        // ── Download flag images ──────────────────────────────────────────────
        $this->info('Downloading country flag images…');
        $flagResult = $this->call('pwa:download-flags');
        if ($flagResult !== self::SUCCESS) {
            $this->warn('  Some flags could not be downloaded. Run php artisan pwa:download-flags later.');
        }

        $this->newLine();
        $this->info('Filament PWA installed successfully.');
        $this->line('  Next steps:');
        $this->line('    • Review config/pwa.php to set your theme and nav items.');
        $this->line('    • If flags are missing, run: php artisan pwa:download-flags');

        return self::SUCCESS;
    }
}