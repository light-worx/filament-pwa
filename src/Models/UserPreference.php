<?php

namespace Lightworx\FilamentPwa\Models;

use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    protected $fillable = [
        'device_id',
        'name',
        'email',
        'email_verification_pin',
        'pin_expires_at',
        'email_verified_at',
        'phone',
        'phone_verified',
        'custom_settings',
        'preaching_reminders'
    ];

    protected $casts = [
        'custom_settings'   => 'array',
        'email_verified_at' => 'datetime',
        'pin_expires_at'    => 'datetime',
        'phone_verified'    => 'boolean',
    ];

    protected $hidden = [
        'email_verification_pin',
    ];

    // ── Accessors ──────────────────────────────────────────────────────────────

    public function getEmailVerifiedAttribute(): bool
    {
        return $this->email_verified_at !== null;
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class, 'user_preference_id');
    }

    public function pushMessages()
    {
        return $this->hasMany(PushMessage::class);
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
}