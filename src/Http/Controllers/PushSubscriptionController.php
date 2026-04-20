<?php

namespace Lightworx\FilamentPwa\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Lightworx\FilamentPwa\Models\PushSubscription;
use Lightworx\FilamentPwa\Models\UserPreference;

class PushSubscriptionController extends Controller
{
    /**
     * Store or update a push subscription.
     *
     * Merge strategy to avoid duplicate UserPreference rows:
     *
     * The browser generates a new push endpoint when subscribe() is called.
     * If the user has already verified their phone (creating a UserPreference
     * with a temporary device_id), we must link the new endpoint to that
     * existing row rather than creating a second one.
     *
     * Resolution order:
     *   1. Exact match on endpoint — subscription already exists, update it.
     *   2. Match on cookie device_id — the user verified on this device using
     *      a temporary id; adopt that row and update its device_id to the endpoint.
     *   3. Match on verified phone — another device_id was used during verification;
     *      adopt that row (most recent verified match wins).
     *   4. No match — create a fresh UserPreference keyed on the endpoint.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint'       => 'required|string',
            'keys.p256dh'    => 'required|string',
            'keys.auth'      => 'required|string',
            'expirationTime' => 'nullable',
        ]);

        $endpoint = $data['endpoint'];

        // ── 1. Existing subscription for this endpoint ────────────────────
        $existing = PushSubscription::where('endpoint', $endpoint)->first();
        if ($existing?->preference) {
            $preference = $existing->preference;
        } else {
            // ── 2. Cookie device_id match ─────────────────────────────────
            $cookieDeviceId = $request->cookie('pwa_device_id');
            $preference     = null;

            if ($cookieDeviceId && $cookieDeviceId !== $endpoint) {
                $preference = UserPreference::where('device_id', $cookieDeviceId)->first();
            }

            // ── 3. Verified phone match ───────────────────────────────────
            if (!$preference) {
                // Look for any verified preference that doesn't already have
                // a push subscription attached to a different endpoint
                $preference = UserPreference::where('phone_verified', true)
                    ->whereNotNull('phone')
                    ->whereDoesntHave('pushSubscriptions')
                    ->latest()
                    ->first();
            }

            // ── 4. Create fresh ───────────────────────────────────────────
            if (!$preference) {
                $preference = new UserPreference();
            }

            // Claim this endpoint as the canonical device_id for this preference
            $preference->device_id = $endpoint;
            $preference->save();
        }

        PushSubscription::updateOrCreate(
            ['endpoint' => $endpoint],
            [
                'public_key'         => $data['keys']['p256dh'],
                'auth_token'         => $data['keys']['auth'],
                'content_encoding'   => 'aesgcm',
                'user_preference_id' => $preference->id,
            ]
        );

        // Sync the endpoint into the response cookie so subsequent page loads
        // have the correct device_id immediately
        return response()
            ->json(['status' => 'subscribed'])
            ->cookie('pwa_device_id', $endpoint, 60 * 24 * 365, '/', null, false, false);
    }

    /**
     * Check whether a given endpoint is subscribed server-side.
     */
    public function status(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => 'required|string',
        ]);

        $subscription = PushSubscription::where('endpoint', $data['endpoint'])
            ->with('preference')
            ->first();

        $phoneVerified = $subscription?->preference?->phone_verified ?? false;

        return response()->json([
            'subscribed'     => $subscription !== null,
            'phone_verified' => (bool) $phoneVerified,
        ]);
    }

    /**
     * Remove a push subscription.
     */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => 'required|string',
        ]);

        PushSubscription::where('endpoint', $data['endpoint'])->delete();

        return response()->json(['status' => 'unsubscribed']);
    }

    /**
     * Called when a push delivery fails with HTTP 410 (Gone).
     */
    public function expire(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => 'required|string',
        ]);

        PushSubscription::where('endpoint', $data['endpoint'])->delete();

        return response()->json(['status' => 'expired']);
    }

    /**
     * Save or update custom settings for a device.
     */
    public function savePreferences(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id'       => 'required|string',
            'custom_settings' => 'nullable|array',
        ]);

        $preference = UserPreference::updateOrCreate(
            ['device_id' => $data['device_id']],
            [
                'custom_settings' => $data['custom_settings'] ?? null,
            ]
        );

        return response()->json([
            'status'        => 'saved',
            'phone_verified' => (bool) $preference->phone_verified,
            'phone'          => $preference->phone,
        ]);
    }

    /**
     * Return saved preferences for the given device_id.
     */
    public function getPreferences(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => 'required|string',
        ]);

        $preference = UserPreference::where('device_id', $data['device_id'])->first();

        if (!$preference) {
            return response()->json((object) []);
        }

        $resolvedName = $preference->resolveIdentityName();

        return response()->json([
            'phone'          => $preference->phone,
            'phone_verified' => (bool) $preference->phone_verified,
            'resolved_name'  => $resolvedName,
            'custom_settings'=> $preference->custom_settings,
        ]);
    }

    /**
     * Toggle the preaching reminders opt-in for a device.
     * Body: { device_id, enabled: true|false }
     */
    public function togglePreachingReminders(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => 'required|string',
            'enabled'   => 'present|boolean',
        ]);

        $preference = UserPreference::where('device_id', $data['device_id'])->first();

        if (!$preference) {
            return response()->json(['message' => 'Device not found.'], 404);
        }

        $preference->update(['preaching_reminders' => $data['enabled']]);
        $preference->refresh();

        return response()->json([
            'status'              => 'ok',
            'preaching_reminders' => (bool) $preference->preaching_reminders,
        ]);
    }
}