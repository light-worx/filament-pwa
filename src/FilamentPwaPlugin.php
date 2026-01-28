<?php

namespace Lightworx\FilamentPwa;

use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentPwaPlugin implements Plugin
{
    public function getId(): string
    {
        return 'pwa';
    }


    public function register(Panel $panel): void
    {
        $panel
            ->registerScripts([
                asset('pwa/register.js'),
            ], true)
            ->registerStyles([
                asset('pwa/pwa.css'),
            ]);
    }


    public function boot(Panel $panel): void
    {
        // No-op
    }
}