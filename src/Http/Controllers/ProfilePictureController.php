<?php

namespace Lightworx\FilamentPwa\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Lightworx\FilamentPwa\Models\UserPreference;

class ProfilePictureController extends Controller
{
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
            // picture_data is a base64 data URI, always sent after client-side compression.
            // We validate the string length rather than using the 'image' rule (which
            // only works on uploaded files). String length cap = maxKb * 1.37 to account
            // for base64 overhead (~137% of raw bytes) plus the data URI prefix.
            'picture'      => "nullable|image|max:{$maxKb}",
            'picture_data' => 'nullable|string|max:' . (int) ($maxKb * 1400),
        ]);

        $preference = UserPreference::where('device_id', $data['device_id'])->first();

        if (!$preference || !$preference->phone_verified) {
            return response()->json(['message' => 'Verified device not found.'], 403);
        }

        // ── Locate the identity record ────────────────────────────────────
        $identityConfig = config('pwa.identity');
        $pictureField   = $identityConfig['picture_field'] ?? null;

        if (empty($identityConfig['model']) || empty($identityConfig['phone_field']) || !$pictureField) {
            return response()->json([
                'message' => 'Identity model or picture_field is not configured in pwa.identity.',
            ], 500);
        }

        $record = app($identityConfig['model'])
            ->where($identityConfig['phone_field'], $preference->phone)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Identity record not found for this device.'], 404);
        }

        // ── Extract image bytes ───────────────────────────────────────────
        if ($request->hasFile('picture')) {
            $contents  = file_get_contents($request->file('picture')->getRealPath());
            $extension = strtolower($request->file('picture')->getClientOriginalExtension() ?: 'jpg');
        } elseif (!empty($data['picture_data'])) {
            if (!preg_match('/^data:image\/(\w+);base64,(.+)$/s', $data['picture_data'], $matches)) {
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

        // ── Delete the previous picture file if it was stored on our disk ─
        // Only delete if it's a bare path (not an external URL).
        $currentValue = data_get($record, $pictureField);
        if ($currentValue && !str_starts_with($currentValue, 'http')) {
            Storage::disk($disk)->delete($currentValue);
        }

        // ── Store the new image ───────────────────────────────────────────
        $filename = Str::uuid() . '.' . $extension;
        $path     = $dir . '/' . $filename;

        Storage::disk($disk)->put($path, $contents);

        // ── Update the identity model ─────────────────────────────────────
        // Use the bare filename/path (not a full URL) so the same field works
        // with Storage::url() for display and Storage::delete() for cleanup.
        $record->$pictureField = $path;
        $record->save();

        return response()->json([
            'status'      => 'uploaded',
            'picture_url' => Storage::disk($disk)->url($path),
        ]);
    }

    /**
     * Remove the uploaded picture from the identity model.
     * Sets the picture_field to null and deletes the file from storage.
     */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['device_id' => 'required|string']);

        $preference = UserPreference::where('device_id', $data['device_id'])->first();

        if (!$preference || !$preference->phone_verified) {
            return response()->json(['message' => 'Verified device not found.'], 403);
        }

        $identityConfig = config('pwa.identity');
        $pictureField   = $identityConfig['picture_field'] ?? null;

        if (empty($identityConfig['model']) || empty($identityConfig['phone_field']) || !$pictureField) {
            return response()->json(['message' => 'Identity model not configured.'], 500);
        }

        $record = app($identityConfig['model'])
            ->where($identityConfig['phone_field'], $preference->phone)
            ->first();

        if ($record) {
            $currentValue = data_get($record, $pictureField);
            if ($currentValue && !str_starts_with($currentValue, 'http')) {
                $disk = config('pwa.picture_upload.disk', 'public');
                Storage::disk($disk)->delete($currentValue);
            }
            $record->$pictureField = null;
            $record->save();
        }

        return response()->json([
            'status'      => 'removed',
            'picture_url' => null,
        ]);
    }
}