<?php

namespace Lightworx\FilamentPwa\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    protected $guarded = ['id'];

    public function preference()
    {
        return $this->belongsTo(UserPreference::class, 'user_preference_id');
    }
}