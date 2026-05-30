<?php

namespace Lightworx\FilamentPwa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One row per person, keyed by phone number.
 *
 * All user-facing settings (custom_settings, phone_verified, etc.) live here.
 * Devices are in the related UserDevice model; push subscriptions hang off
 * those devices, meaning a single preference record fans out to all of a
 * person's registered devices automatically.
 *
 * @property int         $id
 * @property string|null $name
 * @property string|null $email
 * @property string|null $phone
 * @property array|null  $custom_settings
 * @property string|null $phone_verification_pin
 * @property \Carbon\Carbon|null $pin_expires_at
 * @property \Carbon\Carbon|null $email_verified_at
 * @property bool        $phone_verified
 */
class UserPreference extends Model
{
    protected $table = 'user_preferences';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'custom_settings',
        'phone_verification_pin',
        'pin_expires_at',
        'email_verified_at',
        'phone_verified',
    ];

    protected $casts = [
        'custom_settings'   => 'array',
        'pin_expires_at'    => 'datetime',
        'email_verified_at' => 'datetime',
        'phone_verified'    => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    /**
     * All devices that belong to this person.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class, 'user_preference_id');
    }

    /**
     * All push subscriptions across all of this person's devices.
     * Useful for broadcasting to every device in one query.
     */
    public function pushSubscriptions(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            \NotificationChannels\WebPush\PushSubscription::class,
            UserDevice::class,
            'user_preference_id',   // FK on user_devices
            'user_device_id',       // FK on push_subscriptions
            'id',                   // local key on user_preferences
            'id',                   // local key on user_devices
        );
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Read a value from custom_settings, with an optional default.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->custom_settings, $key, $default);
    }

    /**
     * Write one or more values into custom_settings without overwriting
     * other keys.
     */
    public function setSetting(string $key, mixed $value): static
    {
        $settings       = $this->custom_settings ?? [];
        data_set($settings, $key, $value);
        $this->custom_settings = $settings;
        return $this;
    }

    /**
     * Convenience: does this person have at least one active push subscription?
     */
    public function hasSubscribedDevices(): bool
    {
        return $this->devices()->whereHas('pushSubscriptions')->exists();
    }
}