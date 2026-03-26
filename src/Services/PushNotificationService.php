<?php

namespace Lightworx\FilamentPwa\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Lightworx\FilamentPwa\Models\PushSubscription;
use Lightworx\FilamentPwa\Models\UserPreference;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushNotificationService
{
    private WebPush $webPush;

    public function __construct()
    {
        $this->webPush = new WebPush([
            'VAPID' => [
                'subject'    => config('app.url'),
                'publicKey'  => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ],
        ]);

        // Disable auto-padding so we can batch sends efficiently
        $this->webPush->setAutomaticPadding(false);
    }

    // ── Primary send methods ──────────────────────────────────────────────────

    /**
     * Send a push notification to every device registered under a phone number.
     *
     * Usage:
     *   app(PushNotificationService::class)
     *       ->toPhone('+27820000000', 'Hello', 'Your order is ready', '/orders/123');
     *
     *   // or via the facade:
     *   PushNotification::toPhone('+27820000000', 'Hello', 'Your order is ready');
     */
    public function toPhone(
        string $phone,
        string $title,
        string $body  = '',
        string $url   = '/',
        array  $extra = []
    ): SendResult {
        $preferences = UserPreference::where('phone', $phone)
                                     ->where('phone_verified', true)
                                     ->get();

        if ($preferences->isEmpty()) {
            return SendResult::noDevices($phone);
        }

        return $this->dispatch(
            $this->subscriptionsForPreferences($preferences),
            $title, $body, $url, $extra
        );
    }

    /**
     * Send to multiple phone numbers in a single batch.
     *
     * Usage:
     *   PushNotification::toPhones(['+27820000000', '+447911123456'], 'Alert', 'Message');
     */
    public function toPhones(
        array  $phones,
        string $title,
        string $body  = '',
        string $url   = '/',
        array  $extra = []
    ): SendResult {
        $preferences = UserPreference::whereIn('phone', $phones)
                                     ->where('phone_verified', true)
                                     ->get();

        return $this->dispatch(
            $this->subscriptionsForPreferences($preferences),
            $title, $body, $url, $extra
        );
    }

    /**
     * Send to a specific UserPreference (e.g. looked up by your own logic).
     */
    public function toPreference(
        UserPreference $preference,
        string $title,
        string $body  = '',
        string $url   = '/',
        array  $extra = []
    ): SendResult {
        return $this->dispatch(
            $this->subscriptionsForPreferences(collect([$preference])),
            $title, $body, $url, $extra
        );
    }

    /**
     * Broadcast to every subscribed device.
     * Use sparingly — intended for system-wide announcements.
     */
    public function broadcast(
        string $title,
        string $body  = '',
        string $url   = '/',
        array  $extra = []
    ): SendResult {
        return $this->dispatch(PushSubscription::all(), $title, $body, $url, $extra);
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    private function subscriptionsForPreferences(Collection $preferences): Collection
    {
        return PushSubscription::whereIn('user_preference_id', $preferences->pluck('id'))->get();
    }

    private function dispatch(
        Collection $subscriptions,
        string     $title,
        string     $body,
        string     $url,
        array      $extra
    ): SendResult {
        if ($subscriptions->isEmpty()) {
            return SendResult::noDevices();
        }

        $payload = json_encode(array_merge([
            'title' => $title,
            'body'  => $body,
            'url'   => $url,
            'icon'  => '/pwa/icons/icon-192.png',
            'badge' => '/pwa/icons/badge-72.png',
            'tag'   => 'pwa-notification',
        ], $extra));  // $extra can override any of the above

        $queued = 0;

        foreach ($subscriptions as $sub) {
            try {
                $this->webPush->queueNotification(
                    Subscription::create([
                        'endpoint'        => $sub->endpoint,
                        'publicKey'       => $sub->public_key,
                        'authToken'       => $sub->auth_token,
                        'contentEncoding' => $sub->content_encoding ?? 'aesgcm',
                    ]),
                    $payload
                );
                $queued++;
            } catch (\Throwable $e) {
                Log::warning('PWA: failed to queue push notification', [
                    'endpoint' => $sub->endpoint,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        if ($queued === 0) {
            return SendResult::noDevices();
        }

        // Flush — actually sends all queued notifications
        $sent  = 0;
        $failed = 0;
        $stale = [];

        foreach ($this->webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $sent++;
            } else {
                $failed++;
                $statusCode = $report->getResponse()?->getStatusCode();

                // 410 Gone = subscription expired; delete so we stop retrying
                if ($statusCode === 410) {
                    $endpoint = $report->getEndpoint();
                    $stale[]  = $endpoint;
                    PushSubscription::where('endpoint', $endpoint)->delete();
                } else {
                    Log::warning('PWA: push delivery failed', [
                        'endpoint' => $report->getEndpoint(),
                        'reason'   => $report->getReason(),
                        'status'   => $statusCode,
                    ]);
                }
            }
        }

        return new SendResult($sent, $failed, $stale);
    }
}