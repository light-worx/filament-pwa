<?php

namespace Lightworx\FilamentPwa\Models;

use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    protected $fillable = [
        'device_id',
        'name',
        'phone',
        'phone_verification_pin',
        'pin_expires_at',
        'phone_verified',
        'custom_settings',
    ];

    protected $casts = [
        'custom_settings'     => 'array',
        'pin_expires_at'      => 'datetime',
        'phone_verified'      => 'boolean',
    ];

    protected $hidden = [
        'phone_verification_pin',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class, 'user_preference_id');
    }

    public function pushMessages()
    {
        return $this->hasMany(PushMessage::class, 'user_preference_id');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->custom_settings, $key, $default);
    }

    public function setSetting(string $key, mixed $value): void
    {
        $settings       = $this->custom_settings ?? [];
        $settings[$key] = $value;
        $this->update(['custom_settings' => $settings]);
    }

    /**
     * Resolve the display name for this preference's phone number.
     * Delegates to the static lookup so it can be called on an instance.
     */
    public function resolveIdentityName(): ?string
    {
        return static::lookupNameForPhone($this->phone);
    }

    /**
     * Static lookup — find the name in the app's identity model for a phone number.
     * Called both on instances (resolveIdentityName) and before a row exists
     * (in VerificationController::sendPin to gate sending).
     *
     * Returns null when:
     *   - pwa.identity.model is not configured
     *   - no record matches the phone number
     */
    public static function lookupNameForPhone(?string $phone): ?string
    {
        if (!$phone) return null;

        $config = config('pwa.identity');
        if (empty($config['model']) || empty($config['phone_field'])) {
            return null;
        }

        $record = app($config['model'])
            ->where($config['phone_field'], $phone)
            ->first();

        if (!$record) {
            return null;
        }

        return data_get($record, $config['name_field'] ?? 'name');
    }

    /**
     * Check whether a phone number exists in the app's identity model.
     * Used to gate SMS sending when config('pwa.identity.require_known_number') is true.
     */
    public static function phoneExistsInIdentityModel(string $phone): bool
    {
        $config = config('pwa.identity');
        if (empty($config['model']) || empty($config['phone_field'])) {
            // No model configured — can't check, so allow through
            return true;
        }

        return app($config['model'])
            ->where($config['phone_field'], $phone)
            ->exists();
    }
}