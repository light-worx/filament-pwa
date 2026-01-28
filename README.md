# Filament PWA

Progressive Web App support for Filament 4.

## Installation

composer require vendor/filament-pwa

php artisan vendor:publish --tag=filament-pwa-assets

## Usage

In your AdminPanelProvider

use Lightworx\FilamentPwa\FilamentPwaPlugin;

FilamentPwaPlugin::make() 