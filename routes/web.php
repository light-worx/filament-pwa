<?php

use Illuminate\Support\Facades\Route;
use Lightworx\FilamentPwa\Http\Controllers\FieldOptionsController;
use Lightworx\FilamentPwa\Http\Controllers\PushSubscriptionController;
use Lightworx\FilamentPwa\Http\Controllers\VerificationController;

Route::middleware(['web'])->group(function () {

    Route::get(config('pwa.app_route'), function () {
        return view('vendor.pwa.pages.home');
    })->name('app.home');
});

Route::prefix('app')->middleware('web')->group(function () {
 
    // ── Push subscription lifecycle ───────────────────────────────────────────
    Route::post('/subscribe',           [PushSubscriptionController::class, 'store']);
    Route::post('/unsubscribe',         [PushSubscriptionController::class, 'destroy']);
    Route::post('/push/expire',         [PushSubscriptionController::class, 'expire']);
 
    // Check whether a subscription endpoint exists on the server
    // (used on page load to sync browser state with server state)
    Route::post('/push/status',         [PushSubscriptionController::class, 'status']);
 
    // ── Device preferences ────────────────────────────────────────────────────
    Route::post('/preferences',         [PushSubscriptionController::class, 'savePreferences']);
    Route::get('/preferences',          [PushSubscriptionController::class, 'getPreferences']);
 
    // ── Dynamic field options (resolver + AJAX) ────────────────────────────
    // GET  ?search=foo  forwards search term to the resolver for large lists
    Route::get('/field-options/{key}', FieldOptionsController::class)
         ->where('key', '[a-z0-9_]+');
 
    // ── Email verification ────────────────────────────────────────────────────
    Route::post('/verify/send-pin',     [VerificationController::class, 'sendPin']);
    Route::post('/verify/confirm-pin',  [VerificationController::class, 'verifyPin']);
 
    // ── Phone (gated behind email verification) ───────────────────────────────
    Route::post('/verify/phone',        [VerificationController::class, 'savePhone']);
 
});

Route::get('/manifest.json', function () {
    return response()->json([
        'name' => config('app.name'),
        'short_name' => config('app.name'),
        'start_url' => config('pwa.app_route', '/'),
        'description' => config('.pwa.description'),
        'display' => 'standalone',
        'background_color' => '#ffffff',
        'theme_color' => config('pwa.theme.theme_color'),
        'icons' => [
            [
                'src' => config('pwa.icon-192','/pwa/icons/icon-192.png'),
                'sizes' => '192x192',
                'type' => 'image/png',
            ],
            [
                'src' => config('pwa.icon-512','/pwa/icons/icon-512.png'),
                'sizes' => '512x512',
                'type' => 'image/png',
            ],
        ],
    ]);
});