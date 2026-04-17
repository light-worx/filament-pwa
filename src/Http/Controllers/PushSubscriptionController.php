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
            'phone_verified'=> (bool) $preference->phone_verified,
            'phone'         => $preference->phone,
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

        // Resolve display name from the app's configured identity model
        $resolvedName = $preference->resolveIdentityName();
        $notFoundMsg  = null;
        if ($preference->phone_verified && !$resolvedName) {
            $notFoundMsg = config('pwa.identity.not_found_message');
        }

        return response()->json([
            'resolved_name'       => $resolvedName,
            'not_found_message'   => $notFoundMsg,
            'phone'               => $preference->phone,
            'phone_verified'      => (bool) $preference->phone_verified,
            'custom_settings'     => $preference->custom_settings,
        ]);
    }

}