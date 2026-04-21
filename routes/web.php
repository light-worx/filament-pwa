<?php

use Illuminate\Support\Facades\Route;
use Lightworx\FilamentPwa\Http\Controllers\FieldOptionsController;
use Lightworx\FilamentPwa\Http\Controllers\MessagesController;
use Lightworx\FilamentPwa\Http\Controllers\PushSubscriptionController;
use Lightworx\FilamentPwa\Http\Controllers\ProfilePictureController;
use Lightworx\FilamentPwa\Http\Controllers\VerificationController;

/*
 * Build route group attributes from config.
 *
 * Modes:
 *   Path prefix  (route_domain = null): routes live at  /app/subscribe etc.
 *   Subdomain    (route_domain set):    routes live at  app.domain.com/subscribe
 *
 * The prefix and domain values are both read from config so developers only
 * need to set PWA_ROUTE_PREFIX / PWA_ROUTE_DOMAIN in their .env.
 */
$prefix = config('pwa.route_prefix', 'app');
$domain = config('pwa.route_domain');

$groupAttrs = ['middleware' => 'web'];

if ($domain) {
    // Subdomain mode — mount without a path prefix (the subdomain is the prefix)
    $groupAttrs['domain'] = $domain . '.' . parse_url(config('app.url'), PHP_URL_HOST);
} elseif ($prefix !== '') {
    $groupAttrs['prefix'] = $prefix;
}

Route::group($groupAttrs, function () {

    Route::get(config('pwa.app_route', '/'), function () {
        return view('pwa::pages.home');
    })->name('app.home');

    // ── Push subscription lifecycle ───────────────────────────────────────────
    Route::post('/subscribe',           [PushSubscriptionController::class, 'store']);
    Route::post('/unsubscribe',         [PushSubscriptionController::class, 'destroy']);
    Route::post('/push/expire',         [PushSubscriptionController::class, 'expire']);
    Route::post('/push/status',         [PushSubscriptionController::class, 'status']);

    // ── Device preferences ────────────────────────────────────────────────────
    Route::post('/preferences',         [PushSubscriptionController::class, 'savePreferences']);
    Route::get('/preferences',          [PushSubscriptionController::class, 'getPreferences']);

    // ── Dynamic field options ─────────────────────────────────────────────────
    Route::get('/field-options/{key}',  FieldOptionsController::class)
         ->where('key', '[a-z0-9_]+');

    // ── SMS phone verification ────────────────────────────────────────────────
    Route::post('/verify/send-pin',     [VerificationController::class, 'sendPin']);
    Route::post('/verify/confirm-pin',  [VerificationController::class, 'verifyPin']);

    // ── Profile picture ──────────────────────────────────────────────────────
    Route::post('/profile/picture',         [ProfilePictureController::class, 'store']);
    Route::delete('/profile/picture',       [ProfilePictureController::class, 'destroy']);

    // ── Messages inbox ────────────────────────────────────────────────────────
    Route::get('/messages',             [MessagesController::class, 'index'])->name('app.messages');
    Route::get('/messages/list',        [MessagesController::class, 'list']);
    Route::post('/messages/seen',       [MessagesController::class, 'markSeen']);
    Route::post('/messages/delete',     [MessagesController::class, 'destroy']);
    Route::post('/messages/reply',      [MessagesController::class, 'reply']);
    Route::get('/messages/unread',      [MessagesController::class, 'unreadCount']);

});

// ── Manifest — always at the root, never prefixed ─────────────────────────────
Route::get('/manifest.json', function () {
    return response()->json([
        'name'             => config('pwa.app_name'),
        'short_name'       => config('pwa.app_short', config('pwa.app_name')),
        'description'      => config('pwa.description'),
        'start_url'        => config('pwa.app_route', '/'),
        'display'          => 'standalone',
        'background_color' => '#ffffff',
        'theme_color'      => config('pwa.theme.theme_color'),
        'icons'            => [
            [
                'src'   => config('pwa.icon_192', '/pwa/icons/icon-192.png'),
                'sizes' => '192x192',
                'type'  => 'image/png',
            ],
            [
                'src'   => config('pwa.icon_512', '/pwa/icons/icon-512.png'),
                'sizes' => '512x512',
                'type'  => 'image/png',
            ],
        ],
        'screenshots' => [
            [
                'src'         => config('pwa.screenshot', '/pwa/icons/screenshot.png'),
                'sizes'       => '1280x720',
                'type'        => 'image/png',
                'form_factor' => 'wide',
                'description' => 'Home screen',
            ],
            [
                'src'         => config('pwa.icon_512', '/pwa/icons/icon-512.png'),
                'sizes'       => '512x512',
                'type'        => 'image/png',
                'description' => 'App icon',
            ],
        ],
    ]);
})->middleware('web');