<?php

namespace Lightworx\FilamentPwa\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Lightworx\FilamentPwa\Models\PushMessage;
use Lightworx\FilamentPwa\Models\UserPreference;
use Lightworx\FilamentPwa\Services\PushNotificationService;

class MessagesController extends Controller
{
    // ── Page ──────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        return view('pwa::pages.messages');
    }

    // ── API ───────────────────────────────────────────────────────────────────

    /**
     * Return all messages for the calling device, newest first.
     */
    public function list(Request $request): JsonResponse
    {
        $data = $request->validate(['device_id' => 'required|string']);

        $preference = UserPreference::where('device_id', $data['device_id'])->first();

        if (!$preference) {
            return response()->json(['messages' => [], 'unread' => 0]);
        }

        $messages = PushMessage::where('user_preference_id', $preference->id)
            ->orderByDesc('created_at')
            ->get(['id','title','message','sender_name','sender_phone','seen','created_at']);

        return response()->json([
            'messages' => $messages,
            'unread'   => $messages->where('seen', false)->count(),
        ]);
    }

    /**
     * Mark one or more messages as seen/unseen.
     * Body: { device_id, ids: [1,2,3], seen: true }
     */
    public function markSeen(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => 'required|string',
            'ids'       => 'required|array',
            'ids.*'     => 'integer',
            'seen'      => 'required|boolean',
        ]);

        $preference = UserPreference::where('device_id', $data['device_id'])->first();
        if (!$preference) return response()->json(['status' => 'ok']);

        PushMessage::where('user_preference_id', $preference->id)
            ->whereIn('id', $data['ids'])
            ->update(['seen' => $data['seen']]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Delete one or more messages.
     * Body: { device_id, ids: [1,2,3] }
     */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => 'required|string',
            'ids'       => 'required|array',
            'ids.*'     => 'integer',
        ]);

        $preference = UserPreference::where('device_id', $data['device_id'])->first();
        if (!$preference) return response()->json(['status' => 'ok']);

        PushMessage::where('user_preference_id', $preference->id)
            ->whereIn('id', $data['ids'])
            ->delete();

        return response()->json(['status' => 'ok']);
    }

    /**
     * Reply to a message. Sends a push notification back to sender_phone
     * and persists a message in the sender's inbox.
     * Body: { device_id, message_id, body }
     */
    public function reply(Request $request, PushNotificationService $push): JsonResponse
    {
        $data = $request->validate([
            'device_id'  => 'required|string',
            'message_id' => 'required|integer',
            'body'       => 'required|string|max:1000',
        ]);

        $preference = UserPreference::where('device_id', $data['device_id'])->first();
        if (!$preference) {
            return response()->json(['message' => 'Device not found.'], 404);
        }

        $original = PushMessage::where('user_preference_id', $preference->id)
            ->find($data['message_id']);

        if (!$original) {
            return response()->json(['message' => 'Message not found.'], 404);
        }

        if (!$original->sender_phone) {
            return response()->json(['message' => 'This message has no sender phone — cannot reply.'], 422);
        }

        $result = $push->toPhone(
            phone:       $original->sender_phone,
            title:       'Reply from ' . ($preference->name ?? 'a contact'),
            body:        $data['body'],
            url:         '/app/messages',
            senderName:  $preference->name,
            senderPhone: $preference->phone,
        );

        if ($result->noDevices) {
            return response()->json([
                'message' => 'Sent, but the recipient has no registered device.',
            ], 202);
        }

        return response()->json(['status' => 'sent']);
    }

    /**
     * Return unread count only — used by the user-menu badge.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $data = $request->validate(['device_id' => 'required|string']);

        $preference = UserPreference::where('device_id', $data['device_id'])->first();
        if (!$preference) {
            return response()->json(['unread' => 0, 'total' => 0]);
        }

        $total  = PushMessage::where('user_preference_id', $preference->id)->count();
        $unread = PushMessage::where('user_preference_id', $preference->id)
                             ->where('seen', false)->count();

        return response()->json(['unread' => $unread, 'total' => $total]);
    }
}