<?php

namespace Lightworx\FilamentPwa\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Lightworx\FilamentPwa\Models\PushSubscription;
use Lightworx\FilamentPwa\Models\UserDevice;
use Lightworx\FilamentPwa\Models\UserPreference;

class PushSubscriptionController extends Controller
{
    /**
     * Store or update a push subscription.
     *
     * The browser push endpoint is used as the canonical device_id.
     * When the browser calls subscribe() it may already have a temporary
     * device_id (a local UUID set before push was granted). We must carry
     * over the verified phone link from that temporary device to the new
     * endpoint-based device rather than creating an orphaned second device.
     *
     * Resolution order:
     *   1. Exact endpoint match — subscription already exists, update keys only.
     *   2. Cookie device_id match — user verified on this device using a
     *      temporary id; update that UserDevice's device_id to the endpoint.
     *   3. Verified phone match — verification happened on a different device
     *      (e.g. desktop verified, mobile now subscribing); reuse the preference.
     *   4. No match — create a fresh UserDevice (unlinked until verification).
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
        $existingSubscription = PushSubscription::where('endpoint', $endpoint)
            ->with('device.preference')
            ->first();

        if ($existingSubscription?->device) {
            $device = $existingSubscription->device;
        } else {
            // ── 2. Cookie device_id match ─────────────────────────────────
            $cookieDeviceId = $request->cookie('pwa_device_id');
            $device         = null;

            if ($cookieDeviceId && $cookieDeviceId !== $endpoint) {
                $device = UserDevice::where('device_id', $cookieDeviceId)->first();

                if ($device) {
                    // Promote the temporary device_id to the real push endpoint
                    $device->device_id = $endpoint;
                    $device->save();
                }
            }

            // ── 3. Verified phone match ───────────────────────────────────
            if (! $device) {
                // Find a verified preference that does not already have a device
                // row for this exact endpoint — pick the most recent one.
                $preference = UserPreference::where('phone_verified', true)
                    ->whereNotNull('phone')
                    ->whereDoesntHave('devices', function ($q) use ($endpoint) {
                        $q->where('device_id', $endpoint);
                    })
                    ->latest()
                    ->first();

                if ($preference) {
                    // Device row may already exist unlinked — link it rather
                    // than blindly creating a duplicate.
                    $device = UserDevice::firstOrCreate(
                        ['device_id' => $endpoint],
                        ['user_preference_id' => $preference->id]
                    );
                    // If the row already existed but was unlinked, link it now
                    if (! $device->user_preference_id) {
                        $device->user_preference_id = $preference->id;
                        $device->save();
                    }
                }
            }

            // ── 4. Fresh unlinked device ──────────────────────────────────
            // Use firstOrCreate rather than create — the device row may already
            // exist (e.g. created during sendPin) but failed to match in steps
            // 2/3 because the cookie already holds the endpoint value by the
            // time the browser calls subscribe() again.
            if (! $device) {
                $device = UserDevice::firstOrCreate(
                    ['device_id' => $endpoint],
                    ['user_preference_id' => null]
                );
            }
        }

        PushSubscription::updateOrCreate(
            ['endpoint' => $endpoint],
            [
                'public_key'       => $data['keys']['p256dh'],
                'auth_token'       => $data['keys']['auth'],
                'content_encoding' => 'aesgcm',
                'user_device_id'   => $device->id,
            ]
        );

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
            ->with('device.preference')
            ->first();

        $phoneVerified = $subscription?->device?->preference?->phone_verified ?? false;

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
        $data = $request->validate(['endpoint' => 'required|string']);

        PushSubscription::where('endpoint', $data['endpoint'])->delete();

        return response()->json(['status' => 'unsubscribed']);
    }

    /**
     * Called when a push delivery fails with HTTP 410 (Gone).
     */
    public function expire(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => 'required|string']);

        PushSubscription::where('endpoint', $data['endpoint'])->delete();

        return response()->json(['status' => 'expired']);
    }

    /**
     * Save or update custom settings for a device.
     * Settings are stored on UserPreference (shared across all devices for
     * this person), so a change here is immediately visible on every device.
     */
    public function savePreferences(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id'       => 'required|string',
            'custom_settings' => 'nullable|array',
        ]);

        $device = UserDevice::with('preference')->where('device_id', $data['device_id'])->first();

        if (! $device) {
            // Device not yet registered — create it unlinked; settings cannot
            // be persisted until the device is linked via phone verification.
            UserDevice::create(['device_id' => $data['device_id']]);
            return response()->json(['status' => 'saved', 'phone_verified' => false, 'phone' => null]);
        }

        if ($device->preference) {
            // Update the shared preference — affects all linked devices
            $device->preference->update(['custom_settings' => $data['custom_settings'] ?? null]);
            $preference = $device->preference->fresh();
        } else {
            // Device exists but hasn't been verified yet — store a stub preference
            $preference = UserPreference::create([
                'custom_settings' => $data['custom_settings'] ?? null,
            ]);
            $device->user_preference_id = $preference->id;
            $device->save();
        }

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
        $data = $request->validate(['device_id' => 'required|string']);

        $device = UserDevice::with('preference')->where('device_id', $data['device_id'])->first();
        $preference = $device?->preference;

        if (! $preference) {
            return response()->json((object) []);
        }

        try {
            $picture = $preference->resolveProfilePicture();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('PWA: could not resolve profile picture', [
                'phone' => $preference->phone,
                'error' => $e->getMessage(),
            ]);
            $picture = null;
        }

        return response()->json([
            'phone'            => $preference->phone,
            'phone_verified'   => (bool) $preference->phone_verified,
            'resolved_name'    => $preference->resolveIdentityName(),
            'resolved_picture' => $picture,
            'custom_settings'  => $preference->custom_settings,
        ]);
    }

    /**
     * Toggle the preaching reminders opt-in for a device.
     * Body: { device_id, enabled: true|false }
     *
     * Because this writes to UserPreference, toggling on one device
     * automatically applies to all other devices for the same person.
     */
    public function togglePreachingReminders(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => 'required|string',
            'enabled'   => 'present|boolean',
        ]);

        $device = UserDevice::with('preference')->where('device_id', $data['device_id'])->first();
        $preference = $device?->preference;

        if (! $preference) {
            return response()->json(['message' => 'Device not found or not yet verified.'], 404);
        }

        $preference->setSetting('preaching_reminders', (bool) $data['enabled']);
        $preference->save();

        return response()->json([
            'status'              => 'ok',
            'preaching_reminders' => (bool) $preference->getSetting('preaching_reminders'),
        ]);
    }
}