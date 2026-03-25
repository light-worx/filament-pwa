<?php

use Illuminate\Support\Facades\Route;
use Lightworx\FilamentPwa\Http\Controllers\PushSubscriptionController;

Route::middleware(['web'])->group(function () {

    Route::get(config('pwa.app_route'), function () {
        return view('vendor.pwa.pages.home');
    })->name('app.home');

    Route::post('/app/subscribe', [PushSubscriptionController::class, 'store'])
        ->name('pwa.subscribe');
});

Route::get('/manifest.json', function () {
    return response()->json([
        'name' => config('app.name'),
        'short_name' => config('app.name'),
        'start_url' => config('pwa.app_route', '/'),
        'display' => 'standalone',
        'background_color' => '#ffffff',
        'theme_color' => config('pwa.theme_color'),
        'icons' => [
            [
                'src' => '/pwa/icons/icon-192.png',
                'sizes' => '192x192',
                'type' => 'image/png',
            ],
            [
                'src' => '/pwa/icons/icon-512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
            ],
        ],
    ]);
});