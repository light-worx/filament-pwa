<?php

namespace Lightworx\FilamentPwa\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * BulkSMS driver (https://www.bulksms.com)
 *
 * Config (pwa.sms.bulksms):
 *   username  — BulkSMS API token ID
 *   password  — BulkSMS API token secret
 *   from      — Sender ID / name (optional, max 11 chars for alphanumeric)
 */
class BulkSmsDriver implements SmsDriverInterface
{
    private string $username;
    private string $password;
    private ?string $from;

    public function __construct()
    {
        $this->username = config('pwa.sms.bulksms.username', '');
        $this->password = config('pwa.sms.bulksms.password', '');
        $this->from     = config('pwa.sms.bulksms.from')     ?: null;

        if (!$this->username || !$this->password) {
            throw new RuntimeException(
                'BulkSMS credentials not configured. Set pwa.sms.bulksms.username and password.'
            );
        }
    }

    public function send(string $to, string $message): void
    {
        $payload = [
            'to'   => $to,
            'body' => $message,
        ];

        if ($this->from) {
            $payload['from'] = $this->from;
        }

        $response = Http::withBasicAuth($this->username, $this->password)
            ->timeout(15)
            ->post('https://api.bulksms.com/v1/messages', $payload);

        if (!$response->successful()) {
            $detail = $response->json('type') ?? $response->body();
            throw new RuntimeException(
                "BulkSMS delivery failed (HTTP {$response->status()}): {$detail}"
            );
        }
    }
}