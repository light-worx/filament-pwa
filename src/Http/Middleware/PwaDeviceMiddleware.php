<?php

namespace Lightworx\FilamentPwa\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Lightworx\FilamentPwa\Models\UserDevice;
use Symfony\Component\HttpFoundation\Response;

/**
 * PwaDeviceMiddleware
 *
 * Reads the pwa_device_id cookie (written by the JS layer), loads the
 * corresponding UserDevice row, then resolves the linked UserPreference
 * (person-level settings) and makes both available to every controller
 * and Blade view for the duration of the request.
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
 *   // The UserDevice for this browser instance, or null if unrecognised
 *   $device = $request->pwaDevice;
 *
 *   // The shared UserPreference (person-level settings), or null
 *   $preference = $request->pwaPreference;
 *
 *   // Verified phone number, or null
 *   $phone = $request->pwaPhone;
 *
 *   // Any custom setting by key
 *   $circuitId = $request->pwaPreference?->getSetting('circuit_id');
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
        $deviceId  = $request->cookie('pwa_device_id');
        $device     = null;
        $preference = null;

        if ($deviceId) {
            // Load device and eagerly resolve the shared person-level preference
            $device     = UserDevice::with('preference')->where('device_id', $deviceId)->first();
            $preference = $device?->preference;
        }

        // Attach to request — accessible in controllers
        $request->pwaDevice     = $device;
        $request->pwaPreference = $preference;
        $request->pwaPhone      = ($preference?->phone_verified ? $preference->phone : null);

        // Share with all Blade views
        View::share('pwaDevice',     $device);
        View::share('pwaPreference', $preference);
        View::share('pwaPhone',      $request->pwaPhone);

        return $next($request);
    }
}