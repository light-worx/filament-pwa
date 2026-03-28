<?php

namespace Lightworx\FilamentPwa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushMessage extends Model
{
    protected $table = 'push_messages';

    protected $fillable = [
        'title',
        'message',
        'sender_name',
        'sender_phone',
        'user_preference_id',
        'seen',
    ];

    protected $casts = [
        'seen' => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function preference(): BelongsTo
    {
        return $this->belongsTo(UserPreference::class, 'user_preference_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeUnread($query)
    {
        return $query->where('seen', false);
    }

    public function scopeForDevice($query, string $deviceId)
    {
        return $query->whereHas('preference', fn($q) => $q->where('device_id', $deviceId));
    }
}