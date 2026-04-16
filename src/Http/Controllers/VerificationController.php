<?php

namespace Lightworx\FilamentPwa\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Lightworx\FilamentPwa\Models\UserPreference;
use Lightworx\FilamentPwa\Sms\SmsService;

class VerificationController extends Controller
{
    /**
     * Send a 4-digit SMS PIN to the given phone number.
     *
     * When config('pwa.identity.require_known_number') is true, the number
     * must exist in the app's identity model or the request is rejected.
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
            if (!UserPreference::phoneExistsInIdentityModel($phone)) {
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

        // Persist/update the preference row — reset verified until confirmed
        UserPreference::updateOrCreate(
            ['device_id' => $data['device_id']],
            [
                'phone'                  => $phone,
                'phone_verification_pin' => $pin,
                'pin_expires_at'         => now()->addMinutes(15),
                'phone_verified'         => false,
            ]
        );

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
     * Confirm the SMS PIN. On success marks the phone as verified and
     * returns the resolved identity name (or null if not found).
     */
    public function verifyPin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => 'required|string',
            'pin'       => 'required|string|size:4',
        ]);

        $preference = UserPreference::where('device_id', $data['device_id'])->first();

        if (!$preference) {
            return response()->json(['message' => 'Device not found.'], 404);
        }

        if (!$preference->phone_verification_pin || !$preference->pin_expires_at) {
            return response()->json([
                'message' => 'No PIN has been issued. Request a new one.',
            ], 422);
        }

        if (now()->isAfter($preference->pin_expires_at)) {
            return response()->json([
                'message' => 'PIN has expired. Request a new one.',
            ], 422);
        }

        if ($preference->phone_verification_pin !== $data['pin']) {
            return response()->json(['message' => 'Incorrect PIN.'], 422);
        }

        // Verified — clear PIN and mark verified
        $preference->update([
            'phone_verified'         => true,
            'phone_verification_pin' => null,
            'pin_expires_at'         => null,
        ]);

        return response()->json([
            'status'        => 'verified',
            'phone_verified' => true,
            'resolved_name'  => $preference->resolveIdentityName(),
        ]);
    }
}