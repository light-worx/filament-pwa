<?php

namespace Lightworx\FilamentPwa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * One row per registered device.
 *
 * device_id is the push endpoint or local UUID identifying the browser/app
 * instance. Multiple devices can belong to the same UserPreference (person).
 *
 * push_subscriptions.user_device_id → this model's id.
 *
 * @property int         $id
 * @property int|null    $user_preference_id
 * @property string      $device_id
 */
class UserDevice extends Model
{
    protected $table = 'user_devices';

    protected $fillable = [
        'user_preference_id',
        'device_id',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    /**
     * The person (preference) this device belongs to.
     * Null if the device has not yet been linked to a phone number.
     */
    public function preference(): BelongsTo
    {
        return $this->belongsTo(UserPreference::class, 'user_preference_id');
    }

    /**
     * The push subscriptions registered for this device.
     * Typically one, but the webpush package may store multiple endpoints
     * if the user re-subscribes without unsubscribing first.
     */
    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(
            \NotificationChannels\WebPush\PushSubscription::class,
            'user_device_id'
        );
    }

    /**
     * Convenience accessor for the single active push subscription.
     */
    public function pushSubscription(): HasOne
    {
        return $this->hasOne(
            \NotificationChannels\WebPush\PushSubscription::class,
            'user_device_id'
        )->latestOfMany();
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Find a device by its device_id string, optionally scoped to a preference.
     */
    public static function findByDeviceId(string $deviceId, ?int $preferenceId = null): ?static
    {
        return static::when($preferenceId, fn ($q) => $q->where('user_preference_id', $preferenceId))
                     ->where('device_id', $deviceId)
                     ->first();
    }

    /**
     * Link this device to a preference (called after phone verification).
     */
    public function linkToPreference(UserPreference $preference): static
    {
        $this->user_preference_id = $preference->id;
        $this->save();
        return $this;
    }
}