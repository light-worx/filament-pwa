<?php

namespace Lightworx\FilamentPwa\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Lightworx\FilamentPwa\Models\UserPreference;
use Symfony\Component\HttpFoundation\Response;

/**
 * PwaDeviceMiddleware
 *
 * Reads the pwa_device_id cookie (written by the JS layer), loads the
 * corresponding UserPreference row, and makes it available to every
 * controller and Blade view in the request lifecycle.
 *
 * ── Registration ─────────────────────────────────────────────────────────────
 *
 * Laravel 11+  (bootstrap/app.php):
 *
 *   ->withMiddleware(function (Middleware $middleware) {
 *       $middleware->web(append: [
 *           \Lightworx\FilamentPwa\Http\Middleware\PwaDeviceMiddleware::class,
 *       ]);
 *   })
 *
 * Laravel 10  (app/Http/Kernel.php, 'web' group):
 *
 *   \Lightworx\FilamentPwa\Http\Middleware\PwaDeviceMiddleware::class,
 *
 * ── In controllers ────────────────────────────────────────────────────────────
 *
 *   // The full UserPreference for this device, or null if unrecognised
 *   $preference = $request->pwaPreference;
 *
 *   // Verified phone number, or null
 *   $phone = $request->pwaPhone;
 *
 *   // Any custom setting by key
 *   $circuitId = $request->pwaPreference?->getSetting('circuit_id');
 *   $region    = $request->pwaPreference?->getSetting('region');
 *
 * ── In Blade views ────────────────────────────────────────────────────────────
 *
 *   @if($pwaPreference)
 *       Welcome, {{ $pwaPreference->name }}
 *   @endif
 *
 *   <livewire:my-component
 *       :circuit="$pwaPreference?->getSetting('circuit_id')"
 *       :email="$pwaPreference?->email"
 *   />
 */
class PwaDeviceMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $deviceId   = $request->cookie('pwa_device_id');
        $preference = null;

        if ($deviceId) {
            $preference = UserPreference::where('device_id', $deviceId)->first();
        }

        // Attach to request — accessible as $request->pwaPreference in controllers
        $request->pwaPreference = $preference;
        $request->pwaPhone      = ($preference?->phone_verified ? $preference->phone : null);

        // Share with all Blade views
        View::share('pwaPreference', $preference);
        View::share('pwaPhone',      $request->pwaPhone);

        return $next($request);
    }
}