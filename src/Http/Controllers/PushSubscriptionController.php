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
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint'        => 'required|string',
            'keys.p256dh'     => 'required|string',
            'keys.auth'       => 'required|string',
            'expirationTime'  => 'nullable',
        ]);

        $preference = UserPreference::firstOrCreate(
            ['device_id' => $data['endpoint']]
        );

        PushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'public_key'         => $data['keys']['p256dh'],
                'auth_token'         => $data['keys']['auth'],
                'content_encoding'   => 'aesgcm',
                'user_preference_id' => $preference->id,
            ]
        );

        return response()->json(['status' => 'subscribed']);
    }

    /**
     * Check whether a given endpoint is subscribed server-side.
     * Called on page load to sync browser ↔ server state.
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
            'subscribed'    => $subscription !== null,
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
     * Save or update preference fields for a device.
     */
    public function savePreferences(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id'       => 'required|string',
            'name'            => 'nullable|string|max:255',
            'email'           => 'nullable|email|max:255',
            'custom_settings' => 'nullable|array',
        ]);

        $preference = UserPreference::updateOrCreate(
            ['device_id' => $data['device_id']],
            [
                'name'            => $data['name']            ?? null,
                'email'           => $data['email']           ?? null,
                'custom_settings' => $data['custom_settings'] ?? null,
            ]
        );

        return response()->json([
            'status'         => 'saved',
            'email_verified' => (bool) $preference->email_verified_at,
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

        return response()->json([
            'name'                => $preference->name,
            'email'               => $preference->email,
            'email_verified'      => (bool) $preference->email_verified_at,
            'phone'               => $preference->phone,
            'phone_verified'      => (bool) $preference->phone_verified,
            'preaching_reminders' => (bool) $preference->preaching_reminders,
            'custom_settings'     => $preference->custom_settings,
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
            'enabled'   => 'required|boolean',
        ]);

        $preference = UserPreference::where('device_id', $data['device_id'])->first();

        if (!$preference) {
            return response()->json(['message' => 'Device not found.'], 404);
        }

        $preference->update(['preaching_reminders' => $data['enabled']]);

        return response()->json([
            'status'              => 'ok',
            'preaching_reminders' => (bool) $preference->preaching_reminders,
        ]);
    }
}