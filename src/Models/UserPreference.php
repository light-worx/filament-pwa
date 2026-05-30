<?php

namespace Lightworx\FilamentPwa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * One row per person, identified by phone number.
 *
 * All user-facing settings and verification state live here.
 * Devices are stored separately in UserDevice; push subscriptions hang off
 * those devices — meaning a single preference record fans out to all of a
 * person's registered devices automatically.
 */
class UserPreference extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'phone_verification_pin',
        'pin_expires_at',
        'email_verified_at',
        'phone_verified',
        'custom_settings',
    ];

    protected $casts = [
        'custom_settings'   => 'array',
        'pin_expires_at'    => 'datetime',
        'email_verified_at' => 'datetime',
        'phone_verified'    => 'boolean',
    ];

    protected $hidden = [
        'phone_verification_pin',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    /**
     * All devices registered to this person.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class, 'user_preference_id');
    }

    /**
     * All push subscriptions across all of this person's devices.
     * Used by PushNotificationService to fan out to every device in one query.
     */
    public function pushSubscriptions(): HasManyThrough
    {
        return $this->hasManyThrough(
            PushSubscription::class,
            UserDevice::class,
            'user_preference_id', // FK on user_devices
            'user_device_id',     // FK on push_subscriptions
            'id',                 // local key on user_preferences
            'id',                 // local key on user_devices
        );
    }

    /**
     * All push messages sent to this person.
     */
    public function pushMessages(): HasMany
    {
        return $this->hasMany(PushMessage::class, 'user_preference_id');
    }

    // ── Settings helpers ───────────────────────────────────────────────────────

    /**
     * Read a value from custom_settings, with an optional default.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->custom_settings, $key, $default);
    }

    /**
     * Write a single key into custom_settings without overwriting other keys,
     * and immediately persist the change.
     */
    public function setSetting(string $key, mixed $value): void
    {
        $settings       = $this->custom_settings ?? [];
        $settings[$key] = $value;
        $this->update(['custom_settings' => $settings]);
    }

    // ── Identity resolution ────────────────────────────────────────────────────

    /**
     * Resolve the display name for this preference's phone number.
     */
    public function resolveIdentityName(): ?string
    {
        return static::lookupNameForPhone($this->phone);
    }

    /**
     * Resolve the profile picture URL for this preference's phone number.
     */
    public function resolveProfilePicture(): ?string
    {
        return static::lookupPictureForPhone($this->phone);
    }

    /**
     * Find the display name in the app's identity model for a phone number.
     *
     * Called both on instances (via resolveIdentityName) and statically before
     * a preference row exists (e.g. in VerificationController to gate sending).
     *
     * Returns null when pwa.identity.model is not configured or no record matches.
     */
    public static function lookupNameForPhone(?string $phone): ?string
    {
        if (! $phone) return null;

        $config = config('pwa.identity');
        if (empty($config['model']) || empty($config['phone_field'])) {
            return null;
        }

        $record = app($config['model'])
            ->where($config['phone_field'], $phone)
            ->first();

        if (! $record) return null;

        return data_get($record, $config['name_field'] ?? 'name');
    }

    /**
     * Look up the profile picture URL from the app's identity model.
     *
     * Returns null when no picture_field is configured or the record has no image.
     * Handles both full URLs (returned as-is) and bare storage paths (resolved
     * via Storage::url() on the configured disk).
     */
    public static function lookupPictureForPhone(?string $phone): ?string
    {
        if (! $phone) return null;

        $config = config('pwa.identity');
        if (empty($config['model']) || empty($config['phone_field'])
            || empty($config['picture_field'])) {
            return null;
        }

        $record = app($config['model'])
            ->where($config['phone_field'], $phone)
            ->first();

        if (! $record) return null;

        $value = data_get($record, $config['picture_field']);
        if (! $value) return null;

        // Already a full URL — return as-is
        if (str_starts_with($value, 'http')) return $value;

        // Bare path on the upload disk — resolve via Storage::url()
        $disk = config('pwa.picture_upload.disk') ?: 'public';

        try {
            return \Illuminate\Support\Facades\Storage::disk($disk)->url($value);
        } catch (\Throwable) {
            // Disk misconfigured — fall back to a best-effort asset() URL
            return asset('storage/' . ltrim($value, '/'));
        }
    }

    /**
     * Check whether a phone number exists in the app's identity model.
     * Used to gate SMS sending when config('pwa.identity.require_known_number') is true.
     * Returns true (allow through) when no identity model is configured.
     */
    public static function phoneExistsInIdentityModel(string $phone): bool
    {
        $config = config('pwa.identity');
        if (empty($config['model']) || empty($config['phone_field'])) {
            return true;
        }

        return app($config['model'])
            ->where($config['phone_field'], $phone)
            ->exists();
    }

    // ── Device convenience ─────────────────────────────────────────────────────

    /**
     * Whether this person has at least one device with an active push subscription.
     */
    public function hasSubscribedDevices(): bool
    {
        return $this->devices()->whereHas('pushSubscriptions')->exists();
    }
}