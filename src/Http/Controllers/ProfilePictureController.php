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

        // ── Locate where to store the picture ───────────────────────────────
        $identityConfig = config('pwa.identity');
        $pictureField   = $identityConfig['picture_field'] ?? null;
        $usingIdentity  = ! empty($identityConfig['model']) && ! empty($identityConfig['phone_field']) && $pictureField;

        $record = null;

        if ($usingIdentity) {
            $record = app($identityConfig['model'])
                ->where($identityConfig['phone_field'], $preference->phone)
                ->first();

            if (! $record) {
                return response()->json(['message' => 'Identity record not found for this device.'], 404);
            }
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
        $currentValue = $usingIdentity ? data_get($record, $pictureField) : $preference->picture_path;
        if ($currentValue && ! str_starts_with($currentValue, 'http')) {
            Storage::disk($disk)->delete($currentValue);
        }

        // ── Store the new image ───────────────────────────────────────────
        $filename = Str::uuid() . '.' . $extension;
        $path     = $dir . '/' . $filename;

        Storage::disk($disk)->put($path, $contents);

        // ── Persist wherever the picture belongs ───────────────────────────
        if ($usingIdentity) {
            $record->$pictureField = $path;
            $record->save();
        } else {
            $preference->picture_path = $path;
            $preference->save();
        }

        return response()->json([
            'status'      => 'uploaded',
            'picture_url' => Storage::disk($disk)->url($path),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data       = $request->validate(['device_id' => 'required|string']);
        $preference = $this->verifiedPreferenceForDevice($data['device_id']);

        if (! $preference) {
            return response()->json(['message' => 'Verified device not found.'], 403);
        }

        $disk           = config('pwa.picture_upload.disk', 'public');
        $identityConfig = config('pwa.identity');
        $pictureField   = $identityConfig['picture_field'] ?? null;
        $usingIdentity  = ! empty($identityConfig['model']) && ! empty($identityConfig['phone_field']) && $pictureField;

        if ($usingIdentity) {
            $record = app($identityConfig['model'])
                ->where($identityConfig['phone_field'], $preference->phone)
                ->first();

            if ($record) {
                $currentValue = data_get($record, $pictureField);
                if ($currentValue && ! str_starts_with($currentValue, 'http')) {
                    Storage::disk($disk)->delete($currentValue);
                }
                $record->$pictureField = null;
                $record->save();
            }
        } else {
            if ($preference->picture_path && ! str_starts_with($preference->picture_path, 'http')) {
                Storage::disk($disk)->delete($preference->picture_path);
            }
            $preference->picture_path = null;
            $preference->save();
        }

        return response()->json(['status' => 'removed', 'picture_url' => null]);
    }
}