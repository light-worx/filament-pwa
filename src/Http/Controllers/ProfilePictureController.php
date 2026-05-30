<?php

namespace Lightworx\FilamentPwa\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Lightworx\FilamentPwa\Models\UserDevice;

class ProfilePictureController extends Controller
{
    // ── Private helper ────────────────────────────────────────────────────────

    /**
     * Resolve the verified UserPreference for a device_id, or return null.
     */
    private function verifiedPreferenceForDevice(string $deviceId): ?\Lightworx\FilamentPwa\Models\UserPreference
    {
        $preference = UserDevice::with('preference')
            ->where('device_id', $deviceId)
            ->first()
            ?->preference;

        return ($preference?->phone_verified) ? $preference : null;
    }

    // ── Endpoints ─────────────────────────────────────────────────────────────

    /**
     * Upload a profile picture and save it directly to the identity model.
     *
     * The image is stored on the configured disk and the identity record's
     * picture_field is updated — so the picture is immediately visible
     * everywhere in the application that uses that field, not just in the PWA.
     *
     * Accepts either:
     *   - Multipart POST with a 'picture' file field
     *   - JSON POST with a 'picture_data' base64 data URI (camera captures)
     */
    public function store(Request $request): JsonResponse
    {
        $disk  = config('pwa.picture_upload.disk', 'public');
        $dir   = config('pwa.picture_upload.path', 'pwa/avatars');
        $maxKb = max(1, (int) config('pwa.picture_upload.max_kb', 2048));

        $data = $request->validate([
            'device_id'    => 'required|string',
            'picture'      => "nullable|image|max:{$maxKb}",
            'picture_data' => 'nullable|string|max:' . (int) ($maxKb * 1400),
        ]);

        $preference = $this->verifiedPreferenceForDevice($data['device_id']);

        if (! $preference) {
            return response()->json(['message' => 'Verified device not found.'], 403);
        }

        // ── Locate the identity record ────────────────────────────────────
        $identityConfig = config('pwa.identity');
        $pictureField   = $identityConfig['picture_field'] ?? null;

        if (empty($identityConfig['model']) || empty($identityConfig['phone_field']) || ! $pictureField) {
            return response()->json([
                'message' => 'Identity model or picture_field is not configured in pwa.identity.',
            ], 500);
        }

        $record = app($identityConfig['model'])
            ->where($identityConfig['phone_field'], $preference->phone)
            ->first();

        if (! $record) {
            return response()->json(['message' => 'Identity record not found for this device.'], 404);
        }

        // ── Extract image bytes ───────────────────────────────────────────
        if ($request->hasFile('picture')) {
            $contents  = file_get_contents($request->file('picture')->getRealPath());
            $extension = strtolower($request->file('picture')->getClientOriginalExtension() ?: 'jpg');
        } elseif (! empty($data['picture_data'])) {
            if (! preg_match('/^data:image\/(\w+);base64,(.+)$/s', $data['picture_data'], $matches)) {
                return response()->json(['message' => 'Invalid image data.'], 422);
            }
            $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
            $contents  = base64_decode($matches[2]);

            if ($contents === false || strlen($contents) > $maxKb * 1024) {
                return response()->json(['message' => 'Image too large.'], 422);
            }
        } else {
            return response()->json(['message' => 'No image provided.'], 422);
        }

        // ── Delete the previous picture if stored on our disk ─────────────
        $currentValue = data_get($record, $pictureField);
        if ($currentValue && ! str_starts_with($currentValue, 'http')) {
            Storage::disk($disk)->delete($currentValue);
        }

        // ── Store the new image ───────────────────────────────────────────
        $filename = Str::uuid() . '.' . $extension;
        $path     = $dir . '/' . $filename;

        Storage::disk($disk)->put($path, $contents);

        // ── Update the identity model ─────────────────────────────────────
        $record->$pictureField = $path;
        $record->save();

        return response()->json([
            'status'      => 'uploaded',
            'picture_url' => Storage::disk($disk)->url($path),
        ]);
    }

    /**
     * Remove the uploaded picture from the identity model.
     */
    public function destroy(Request $request): JsonResponse
    {
        $data       = $request->validate(['device_id' => 'required|string']);
        $preference = $this->verifiedPreferenceForDevice($data['device_id']);

        if (! $preference) {
            return response()->json(['message' => 'Verified device not found.'], 403);
        }

        $identityConfig = config('pwa.identity');
        $pictureField   = $identityConfig['picture_field'] ?? null;

        if (empty($identityConfig['model']) || empty($identityConfig['phone_field']) || ! $pictureField) {
            return response()->json(['message' => 'Identity model not configured.'], 500);
        }

        $record = app($identityConfig['model'])
            ->where($identityConfig['phone_field'], $preference->phone)
            ->first();

        if ($record) {
            $currentValue = data_get($record, $pictureField);
            if ($currentValue && ! str_starts_with($currentValue, 'http')) {
                Storage::disk($disk = config('pwa.picture_upload.disk', 'public'))->delete($currentValue);
            }
            $record->$pictureField = null;
            $record->save();
        }

        return response()->json(['status' => 'removed', 'picture_url' => null]);
    }
}