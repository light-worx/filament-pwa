<?php

namespace Lightworx\FilamentPwa\Sms;

use RuntimeException;

class SmsService
{
    private SmsDriverInterface $driver;

    public function __construct()
    {
        $this->driver = $this->resolveDriver();
    }

    /**
     * Send a verification PIN to the given phone number.
     */
    public function sendPin(string $to, string $pin): void
    {
        $appName = config('pwa.app_name', config('app.name'));
        $message = "{$appName}: your verification code is {$pin}. Valid for 15 minutes.";
        $this->driver->send($to, $message);
    }

    /**
     * Send an arbitrary SMS (for push notification delivery fallback etc.)
     */
    public function send(string $to, string $message): void
    {
        $this->driver->send($to, $message);
    }

    private function resolveDriver(): SmsDriverInterface
    {
        $driver = config('pwa.sms.driver', 'bulksms');

        return match ($driver) {
            'bulksms' => new BulkSmsDriver(),
            // Add future drivers here:
            // 'twilio'  => new TwilioDriver(),
            // 'vonage'  => new VonageDriver(),
            default   => throw new RuntimeException(
                "Unknown PWA SMS driver '{$driver}'. Supported: bulksms."
            ),
        };
    }
}