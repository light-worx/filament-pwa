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
     * Upload or replace the profile picture for a device.
     *
     * Accepts a multipart POST with:
     *   device_id — the device identifier
     *   picture   — the image file (JPEG, PNG, GIF, WebP)
     *
     * Also accepts a base64-encoded image for camera captures:
     *   device_id    — the device identifier
     *   picture_data — base64 data URI (data:image/jpeg;base64,...)
     */
    public function store(Request $request): JsonResponse
    {
        $disk    = config('pwa.picture_upload.disk', 'public');
        $dir     = config('pwa.picture_upload.path', 'pwa/avatars');
        $maxKb   = (int) config('pwa.picture_upload.max_kb', 2048);
        $maxKb   = max(1, $maxKb);

        $data = $request->validate([
            'device_id'    => 'required|string',
            'picture'      => "nullable|image|max:{$maxKb}",
            'picture_data' => 'nullable|string',   // base64 data URI from camera
        ]);

        $preference = UserPreference::where('device_id', $data['device_id'])->first();

        if (!$preference || !$preference->phone_verified) {
            return response()->json(['message' => 'Verified device not found.'], 403);
        }

        // ── Determine image content ───────────────────────────────────────
        if ($request->hasFile('picture')) {
            $contents  = file_get_contents($request->file('picture')->getRealPath());
            $extension = $request->file('picture')->getClientOriginalExtension() ?: 'jpg';
        } elseif (!empty($data['picture_data'])) {
            // Strip the data URI prefix: data:image/jpeg;base64,....
            if (!preg_match('/^data:image\/(\w+);base64,(.+)$/', $data['picture_data'], $matches)) {
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

        // ── Delete previous upload if it exists ───────────────────────────
        if ($preference->picture_path) {
            Storage::disk($disk)->delete($preference->picture_path);
        }

        // ── Store the new image ───────────────────────────────────────────
        $filename = Str::uuid() . '.' . strtolower($extension);
        $path     = $dir . '/' . $filename;

        Storage::disk($disk)->put($path, $contents);

        $preference->update(['picture_path' => $path]);

        return response()->json([
            'status'      => 'uploaded',
            'picture_url' => Storage::disk($disk)->url($path),
        ]);
    }

    /**
     * Remove the user-uploaded picture, reverting to the identity model picture.
     */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => 'required|string',
        ]);

        $preference = UserPreference::where('device_id', $data['device_id'])->first();

        if (!$preference || !$preference->phone_verified) {
            return response()->json(['message' => 'Verified device not found.'], 403);
        }

        if ($preference->picture_path) {
            $disk = config('pwa.picture_upload.disk', 'public');
            Storage::disk($disk)->delete($preference->picture_path);
            $preference->update(['picture_path' => null]);
        }

        // Return the fallback picture (from identity model, or null)
        return response()->json([
            'status'      => 'removed',
            'picture_url' => $preference->resolveProfilePicture(),
        ]);
    }
}