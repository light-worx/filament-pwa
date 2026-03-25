# Filament PWA

Progressive Web App support for Filament 4.

## Installation

composer require vendor/filament-pwa

php artisan vendor:publish --tag=filament-pwa-assets

## Usage

After publishing assets, add the following to your main layout:

  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="{{ config('pwa.theme_color') }}">
    
  <script>
      if ('serviceWorker' in navigator') {
        navigator.serviceWorker.register('/pwa/service-worker.js');
      }
  </script>
