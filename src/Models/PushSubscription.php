<?php

namespace Lightworx\FilamentPwa\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    protected $fillable = [
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
        'user_preference_id',
    ];

    protected $casts = [];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function preference()
    {
        return $this->belongsTo(UserPreference::class, 'user_preference_id');
    }
}