<?php

namespace Lightworx\FilamentPwa\Sms;

interface SmsDriverInterface
{
    /**
     * Send an SMS message to the given E.164 phone number.
     *
     * @throws \RuntimeException on delivery failure
     */
    public function send(string $to, string $message): void;
}