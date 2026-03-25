<?php

namespace Lightworx\FilamentPwa\Models;

use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'custom_settings' => 'array',
    ];

    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class, 'user_preference_id');
    }
}