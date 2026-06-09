<?php

namespace Lightworx\FilamentPwa\Services;

use Lightworx\FilamentPwa\Models\PushMessage;
use Lightworx\FilamentPwa\Models\UserDevice;
use Lightworx\FilamentPwa\Models\UserPreference;
use Lightworx\FilamentPwa\DTOs\SendResult;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * Sends push notifications via Web Push (VAPID).
 *
 * All public methods accept the message payload and return a SendResult.
 *
 * The key behavioural change from the old device-per-preference design:
 *   - Settings (custom_settings, phone_verified, etc.) are read from the
 *     single UserPreference row for a phone number.
 *   - Push subscriptions are fanned out across ALL UserDevice rows linked
 *     to that preference, so every device the person has registered receives
 *     the notification automatically.
 *   - Changing a setting on one device updates the shared UserPreference row,
 *     so the change is immediately visible on every other device.
 */
class PushNotificationService
{
    private WebPush $webPush;

    public function __construct()
    {
        $this->webPush = new WebPush([
            'VAPID' => [
                'subject'    => config('pwa.vapid.subject'),
                'publicKey'  => config('pwa.vapid.public_key'),
                'privateKey' => config('pwa.vapid.private_key'),
            ],
        ]);
    }

    // ── Public API (matches existing Facade surface) ───────────────────────────

    /**
     * Send to a single phone number.
     * Looks up the UserPreference for that phone and dispatches to all
     * linked devices.
     */
    public function toPhone(
        string  $phone,
        string  $title,
        string  $body,
        ?string $url        = null,
        ?string $senderName = null,
    ): SendResult {
        $preference = UserPreference::where('phone', $phone)->first();

        if (! $preference) {
            return SendResult::noDevices();
        }

        return $this->toPreference($preference, $title, $body, $url, $senderName);
    }

    /**
     * Send to multiple phone numbers in one call.
     * Returns an aggregated SendResult.
     */
    public function toPhones(
        array   $phones,
        string  $title,
        string  $body,
        ?string $url        = null,
        ?string $senderName = null,
    ): SendResult {
        $sent      = 0;
        $failed    = 0;
        $noDevices = 0;

        foreach ($phones as $phone) {
            $result = $this->toPhone($phone, $title, $body, $url, $senderName);
            $sent      += $result->sent;
            $failed    += $result->failed;
            $noDevices += $result->noDevices ? 1 : 0;
        }

        return new SendResult(sent: $sent, failed: $failed, noDevices: $noDevices === count($phones));
    }

    /**
     * Send to a specific UserPreference instance (all linked devices).
     * This is the core dispatch method; all other methods funnel through here.
     */
    public function toPreference(
        UserPreference $preference,
        string         $title,
        string         $body,
        ?string        $url        = null,
        ?string        $senderName = null,
    ): SendResult {
        // Load all push subscriptions for all devices linked to this preference
        $subscriptions = $preference->pushSubscriptions()->get();

        if ($subscriptions->isEmpty()) {
            return SendResult::noDevices();
        }

        $payload = $this->buildPayload($title, $body, $url);

        foreach ($subscriptions as $sub) {
            $this->webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'keys'     => [
                        'p256dh' => $sub->public_key,
                        'auth'   => $sub->auth_token,
                    ],
                ]),
                $payload
            );
        }

        [$sent, $failed] = $this->flush();

        if ($sent > 0) {
            $this->recordMessage($preference, $title, $body, $senderName);
        }

        return new SendResult(sent: $sent, failed: $failed, noDevices: false);
    }

    /**
     * Broadcast to every UserPreference that has at least one subscribed device.
     */
    public function broadcast(
        string  $title,
        string  $body,
        ?string $url        = null,
        ?string $senderName = null,
    ): SendResult {
        $sent   = 0;
        $failed = 0;

        UserPreference::whereHas('devices.pushSubscriptions')
            ->each(function (UserPreference $preference) use ($title, $body, $url, $senderName, &$sent, &$failed) {
                $result  = $this->toPreference($preference, $title, $body, $url, $senderName);
                $sent   += $result->sent;
                $failed += $result->failed;
            });

        return new SendResult(sent: $sent, failed: $failed, noDevices: $sent === 0 && $failed === 0);
    }

    // ── Device management helpers (called from PWA JS layer) ──────────────────

    /**
     * Register a new device, optionally linking it to an existing preference
     * by phone number.  If no preference exists for the phone yet, one is
     * created.  If no phone is supplied the device is registered anonymously
     * and can be linked later via linkDeviceToPhone().
     *
     * Returns the UserDevice that was created or updated.
     */
    public function registerDevice(string $deviceId, ?string $phone = null): UserDevice
    {
        $preference = null;

        if ($phone) {
            $preference = UserPreference::firstOrCreate(
                ['phone' => $phone],
                ['phone_verified' => false]
            );
        }

        return UserDevice::updateOrCreate(
            ['device_id' => $deviceId],
            ['user_preference_id' => $preference?->id]
        );
    }

    /**
     * Link an anonymous (unverified) device to a preference after the user
     * completes phone verification.
     */
    public function linkDeviceToPhone(string $deviceId, string $phone): ?UserDevice
    {
        $device     = UserDevice::where('device_id', $deviceId)->first();
        $preference = UserPreference::where('phone', $phone)->first();

        if (! $device || ! $preference) {
            return null;
        }

        return $device->linkToPreference($preference);
    }

    /**
     * Update a setting on the shared preference for a given device.
     * Because settings live on UserPreference (not UserDevice), this change
     * is immediately reflected for every other device linked to the same person.
     */
    public function updateSetting(string $deviceId, string $key, mixed $value): bool
    {
        $device = UserDevice::with('preference')->where('device_id', $deviceId)->first();

        if (! $device?->preference) {
            return false;
        }

        $device->preference->setSetting($key, $value)->save();

        return true;
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function buildPayload(string $title, string $body, ?string $url): string
    {
        return json_encode(array_filter([
            'title' => $title,
            'body'  => $body,
            'icon'  => config('filament-pwa.icons.notification', '/icons/notification.png'),
            'badge' => config('filament-pwa.icons.badge', '/icons/badge.png'),
            'data'  => $url ? ['url' => $url] : null,
        ]));
    }

    /**
     * Flush the WebPush queue and handle expired subscriptions.
     *
     * @return array{int, int} [$sent, $failed]
     */
    private function flush(): array
    {
        $sent   = 0;
        $failed = 0;

        foreach ($this->webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $sent++;
            } else {
                $failed++;

                // Remove subscriptions the browser has declared expired/invalid
                if ($report->isSubscriptionExpired()) {
                    $endpoint = $report->getRequest()->getUri()->__toString();

                    \NotificationChannels\WebPush\PushSubscription::where('endpoint', $endpoint)
                        ->delete();
                }
            }
        }

        return [$sent, $failed];
    }

    /**
     * Write a PushMessage audit record to the database.
     */
    private function recordMessage(
        UserPreference $preference,
        string         $title,
        string         $body,
        ?string        $senderName,
    ): void {
        PushMessage::create([
            'title'              => $title,
            'message'            => $body,
            'sender_name'        => $senderName ?? config('app.name', 'System'),
            'sender_phone'       => null,
            'user_preference_id' => $preference->id,
            'seen'               => false,
        ]);
    }
}