<?php

namespace Lightworx\FilamentPwa\Facades;

use Illuminate\Support\Facades\Facade;
use Lightworx\FilamentPwa\Services\PushNotificationService;
use Lightworx\FilamentPwa\Services\SendResult;

/**
 * @method static SendResult toPhone(string $phone, string $title, string $body = '', string $url = '/', array $extra = [])
 * @method static SendResult toPhones(array $phones, string $title, string $body = '', string $url = '/', array $extra = [])
 * @method static SendResult toPreference(\Lightworx\FilamentPwa\Models\UserPreference $preference, string $title, string $body = '', string $url = '/', array $extra = [])
 * @method static SendResult broadcast(string $title, string $body = '', string $url = '/', array $extra = [])
 *
 * @see PushNotificationService
 */
class PushNotification extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PushNotificationService::class;
    }
}