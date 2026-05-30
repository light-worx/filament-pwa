<?php

namespace Lightworx\FilamentPwa\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Lightworx\FilamentPwa\Models\UserDevice;
use Lightworx\FilamentPwa\Models\UserPreference;
use Lightworx\FilamentPwa\Sms\SmsService;

class VerificationController extends Controller
{
    /**
     * Send a 4-digit SMS PIN to the given phone number.
     *
     * Finds or creates the UserPreference for this phone number and stores
     * the PIN there. The device is looked up separately via device_id and
     * will be linked to the preference when the PIN is confirmed.
     *
     * Rate-limited to 3 attempts per device per 10 minutes.
     */
    public function sendPin(Request $request, SmsService $sms): JsonResponse
    {
        $data = $request->validate([
            'device_id' => 'required|string',
            'phone'     => 'required|string|max:20',
        ]);

        // Normalise to E.164 — strip leading zero after dial code
        $phone = preg_replace('/^(\+\d{1,3})0(\d)/', '$1$2', $data['phone']);

        // ── Gate: reject unknown numbers if configured ────────────────────
        if (config('pwa.identity.require_known_number', true)) {
            if (! UserPreference::phoneExistsInIdentityModel($phone)) {
                return response()->json([
                    'message' => config(
                        'pwa.identity.unknown_message',
                        'This number is not registered on this site.'
                    ),
                ], 403);
            }
        }

        // ── Rate limit ────────────────────────────────────────────────────
        $key = 'pwa-sms-pin:' . $data['device_id'];

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => "Too many attempts. Please wait {$seconds} seconds.",
            ], 429);
        }

        RateLimiter::hit($key, 600); // 10-minute window

        $pin = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        // Find or create the person-level preference for this phone number.
        // The PIN is stored here (on the person), not on the device, so if
        // the user switches devices mid-verification it still works.
        $preference = UserPreference::updateOrCreate(
            ['phone' => $phone],
            [
                'phone_verification_pin' => $pin,
                'pin_expires_at'         => now()->addMinutes(15),
                'phone_verified'         => false,
            ]
        );

        // Ensure a UserDevice row exists for this device_id. It may already
        // exist (e.g. the user subscribed to push before verifying). We do
        // not link it to the preference yet — that happens in verifyPin()
        // once the PIN is confirmed.
        UserDevice::firstOrCreate(['device_id' => $data['device_id']]);

        // ── Send SMS ──────────────────────────────────────────────────────
        try {
            $sms->sendPin($phone, $pin);
        } catch (\Throwable $e) {
            Log::error('PWA SMS send failed', ['phone' => $phone, 'error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Could not send SMS. Please try again shortly.',
            ], 503);
        }

        return response()->json(['status' => 'sent']);
    }

    /**
     * Confirm the SMS PIN.
     *
     * On success:
     *   - Marks the phone as verified on UserPreference.
     *   - Links the UserDevice to the UserPreference (the moment where a
     *     device becomes "owned" by a person).
     *   - If any other UserDevice already exists for this phone but is
     *     unlinked, leaves them unlinked — they must verify independently.
     */
    public function verifyPin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => 'required|string',
            'pin'       => 'required|string|size:4',
        ]);

        // Look up the device
        $device = UserDevice::with('preference')->where('device_id', $data['device_id'])->first();

        if (! $device) {
            return response()->json(['message' => 'Device not found.'], 404);
        }

        // The preference may already be linked (re-verification flow), or we
        // may need to find it via the phone that was set during sendPin().
        // We find it by matching the PIN against all non-expired preferences.
        $preference = UserPreference::where('phone_verification_pin', $data['pin'])
            ->where('pin_expires_at', '>', now())
            ->first();

        if (! $preference) {
            // Try via the device's already-linked preference (handles the case
            // where the device was linked before verification ran)
            $preference = $device->preference;

            if (! $preference ||
                $preference->phone_verification_pin !== $data['pin'] ||
                now()->isAfter($preference->pin_expires_at ?? now())
            ) {
                if ($preference && now()->isAfter($preference->pin_expires_at ?? now())) {
                    return response()->json(['message' => 'PIN has expired. Request a new one.'], 422);
                }
                return response()->json(['message' => 'Incorrect PIN.'], 422);
            }
        }

        // Verified — clear PIN, mark verified, link this device to the preference
        $preference->update([
            'phone_verified'         => true,
            'phone_verification_pin' => null,
            'pin_expires_at'         => null,
        ]);

        // Link the device to the person — from this point on all settings and
        // messages for this phone number are shared with this device
        $device->user_preference_id = $preference->id;
        $device->save();

        return response()->json([
            'status'         => 'verified',
            'phone_verified' => true,
            'resolved_name'  => $preference->resolveIdentityName(),
        ]);
    }
}