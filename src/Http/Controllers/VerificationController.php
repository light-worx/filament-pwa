<?php

namespace Lightworx\FilamentPwa\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Lightworx\FilamentPwa\Mail\EmailVerificationPin;
use Lightworx\FilamentPwa\Models\UserPreference;

class VerificationController extends Controller
{
    /**
     * Send a 4-digit PIN to the given email address.
     * Rate-limited to 3 attempts per device per 10 minutes.
     */
    public function sendPin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => 'required|string',
            'email'     => 'required|email',
        ]);

        $key = 'pwa-pin:' . $data['device_id'];

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => "Too many attempts. Please wait {$seconds} seconds.",
            ], 429);
        }

        RateLimiter::hit($key, 600); // 10-minute window

        $pin = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        $preference = UserPreference::updateOrCreate(
            ['device_id' => $data['device_id']],
            [
                'email'                  => $data['email'],
                'email_verification_pin' => $pin,
                'pin_expires_at'         => now()->addMinutes(15),
                // Clear previous verification if email changed
                'email_verified_at'      => null,
                'phone_verified'         => false,
            ]
        );

        Mail::to($data['email'])->send(new EmailVerificationPin($pin, config('pwa.app_name')));

        return response()->json(['status' => 'sent']);
    }

    /**
     * Verify the PIN submitted by the user.
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

        if (!$preference->email_verification_pin || !$preference->pin_expires_at) {
            return response()->json(['message' => 'No PIN has been issued. Request a new one.'], 422);
        }

        if (now()->isAfter($preference->pin_expires_at)) {
            return response()->json(['message' => 'PIN has expired. Request a new one.'], 422);
        }

        if ($preference->email_verification_pin !== $data['pin']) {
            return response()->json(['message' => 'Incorrect PIN.'], 422);
        }

        // Verified — clear PIN and mark email as verified
        $preference->update([
            'email_verified_at'      => now(),
            'email_verification_pin' => null,
            'pin_expires_at'         => null,
        ]);

        return response()->json([
            'status'         => 'verified',
            'email_verified' => true,
        ]);
    }

    /**
     * Save a verified phone number.
     * Only allowed when email is already verified.
     */
    public function savePhone(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => 'required|string',
            'phone'     => 'required|string|max:20',
        ]);

        $preference = UserPreference::where('device_id', $data['device_id'])->first();

        if (!$preference || !$preference->email_verified_at) {
            return response()->json([
                'message' => 'Email must be verified before saving a phone number.',
            ], 403);
        }

        // Normalise to E.164: ensure no double leading digit after dial code.
        // Handles the case where the client sends +270794999139 instead of +27794999139.
        // Pattern: if after the + and country digits there's a 0, remove it.
        $phone = preg_replace('/^(\+\d{1,3})0(\d)/', '$1$2', $data['phone']);

        $preference->update([
            'phone'          => $phone,
            'phone_verified' => true,   // trusted because email is verified
        ]);

        return response()->json(['status' => 'saved', 'phone' => $data['phone']]);
    }
}