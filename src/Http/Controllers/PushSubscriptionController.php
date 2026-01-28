<?php

namespace Lightworx\FilamentPwa\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $request->user()->updatePushSubscription(
            $request->endpoint,
            $request->keys['p256dh'],
            $request->keys['auth'],
            $request->contentEncoding
        );
        return response()->json(['status' => 'subscribed']);
    }
}