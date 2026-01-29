<?php

namespace Lightworx\FilamentPwa\Console;

use Illuminate\Console\Command;

class InstallFilamentPwaCommand extends Command
{
    protected $signature = 'filament-pwa:install {--force : Overwrite existing files}';

    protected $description = 'Install the Filament PWA plugin';

    public function handle(): int
    {
        $this->info('Installing Filament PWA plugin...');

        $force = $this->option('force') ? ['--force' => true] : [];

        /*
         |---------------------------------------------------------
         | Publish WebPush (notification-channels/webpush)
         |---------------------------------------------------------
         */

        if (env('VAPID_PUBLIC_KEY') && env('VAPID_PRIVATE_KEY')){
            $this->newLine();
            $this->info('VAPID keys already set.');
        } else {
            $this->info('Publishing WebPush config and migrations...');

            $this->call('vendor:publish', array_merge([
                '--provider' => 'NotificationChannels\WebPush\WebPushServiceProvider',
            ], $force));
            $this->newLine();
            $this->call('webpush:vapid');
            $this->newLine();
            $this->call('migrate');
        }
        $this->newLine();

        /*
         |---------------------------------------------------------
         | Publish Filament PWA package files
         |---------------------------------------------------------
         */
        $this->info('Publishing Filament PWA assets...');

        $this->call('vendor:publish', array_merge([
            '--tag' => 'filament-pwa-config',
        ], $force));

        $this->call('vendor:publish', array_merge([
            '--tag' => 'filament-pwa-assets',
        ], $force));

        $this->call('vendor:publish', array_merge([
            '--tag' => 'filament-pwa-views',
        ], $force));

        $this->call('vendor:publish', array_merge([
            '--tag' => 'filament-pwa-public',
        ], $force));

        $this->newLine();
        $this->info('Filament PWA installed successfully.');

        return self::SUCCESS;
    }
}
