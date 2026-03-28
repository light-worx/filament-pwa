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
 * Reads the pwa_device_id cookie written by push-notifications.js and
 * user-menu.blade.php, loads the corresponding UserPreference row, and
 * shares it with every view plus attaches it to the request.
 *
 * This replaces cookie-based workarounds like $_COOKIE['user_circuit'].
 *
 * ── Registration ─────────────────────────────────────────────────────────────
 *
 * Add to your app's HTTP kernel (app/Http/Kernel.php) in the 'web' group:
 *
 *   \Lightworx\FilamentPwa\Http\Middleware\PwaDeviceMiddleware::class,
 *
 * Or in Laravel 11+ bootstrap/app.php:
 *
 *   ->withMiddleware(function (Middleware $middleware) {
 *       $middleware->web(append: [
 *           \Lightworx\FilamentPwa\Http\Middleware\PwaDeviceMiddleware::class,
 *       ]);
 *   })
 *
 * ── Usage in controllers ──────────────────────────────────────────────────────
 *
 *   // The UserPreference for this device (null if not recognised)
 *   $preference = $request->pwaPreference;
 *
 *   // Shorthand helpers
 *   $circuitId = $request->pwaCircuitId;       // custom_settings.circuit_id
 *   $phone     = $request->pwaPhone;           // verified phone number or null
 *
 * ── Usage in Blade views ──────────────────────────────────────────────────────
 *
 *   {{-- All three are shared automatically --}}
 *   @if($pwaPreference)
 *       Welcome, {{ $pwaPreference->name }}
 *   @endif
 *
 *   @if($pwaCircuitId)
 *       {{-- redirect or filter by circuit --}}
 *   @endif
 *
 *   <livewire:my-component :prefilledCircuit="$pwaCircuitId" :prefilledEmail="$pwaPreference?->email" />
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

        // Attach to the request so controllers can type-hint or use $request->pwaPreference
        $request->merge([]);   // ensure request is mutable
        $request->pwaPreference = $preference;
        $request->pwaCircuitId  = $preference?->getSetting('circuit_id');
        $request->pwaPhone      = ($preference?->phone_verified ? $preference?->phone : null);

        // Share with all Blade views
        View::share('pwaPreference', $preference);
        View::share('pwaCircuitId',  $preference?->getSetting('circuit_id'));
        View::share('pwaPhone',      $preference?->phone_verified ? $preference?->phone : null);

        return $next($request);
    }
}