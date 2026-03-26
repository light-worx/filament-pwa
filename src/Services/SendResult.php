<?php

namespace Lightworx\FilamentPwa\Services;

/**
 * Immutable result returned by every PushNotificationService send method.
 *
 * Usage:
 *   $result = PushNotification::toPhone('+27820000000', 'Hello', 'World');
 *
 *   $result->sent;           // int   — successfully delivered
 *   $result->failed;         // int   — delivery failures
 *   $result->stale;          // array — endpoints deleted because they returned 410
 *   $result->noDevices;      // bool  — true when no subscriptions were found at all
 *   $result->total();        // int   — sent + failed
 *   $result->allDelivered(); // bool  — failed === 0 && sent > 0
 */
final class SendResult
{
    public function __construct(
        public readonly int   $sent      = 0,
        public readonly int   $failed    = 0,
        public readonly array $stale     = [],
        public readonly bool  $noDevices = false,
        public readonly ?string $target  = null,  // phone / label for logging
    ) {}

    public static function noDevices(?string $target = null): self
    {
        return new self(noDevices: true, target: $target);
    }

    public function total(): int
    {
        return $this->sent + $this->failed;
    }

    public function allDelivered(): bool
    {
        return !$this->noDevices && $this->failed === 0 && $this->sent > 0;
    }

    public function toArray(): array
    {
        return [
            'sent'       => $this->sent,
            'failed'     => $this->failed,
            'stale'      => $this->stale,
            'no_devices' => $this->noDevices,
        ];
    }
}