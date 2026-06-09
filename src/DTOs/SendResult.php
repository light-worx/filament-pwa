<?php

namespace Lightworx\FilamentPwa\DTOs;

class SendResult
{
    public function __construct(
        public readonly int  $sent      = 0,
        public readonly int  $failed    = 0,
        public readonly bool $noDevices = false,
    ) {}

    /**
     * Convenience constructor for the "no subscriptions found" case.
     */
    public static function noDevices(): static
    {
        return new static(sent: 0, failed: 0, noDevices: true);
    }
}