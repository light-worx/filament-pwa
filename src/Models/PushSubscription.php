<?php

namespace Lightworx\FilamentPwa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    protected $fillable = [
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
        'user_device_id',
    ];

    protected $casts = [];

    // ── Relationships ──────────────────────────────────────────────────────────

    /**
     * The device this subscription belongs to.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(UserDevice::class, 'user_device_id');
    }

    /**
     * Convenience accessor — resolve the person-level preference directly
     * from a subscription without having to chain ->device->preference
     * at every call site.
     */
    public function preference(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            UserPreference::class,
            UserDevice::class,
            'id',                // FK on user_devices that links to this subscription
            'id',                // FK on user_preferences
            'user_device_id',    // local key on push_subscriptions
            'user_preference_id' // local key on user_devices
        );
    }
}