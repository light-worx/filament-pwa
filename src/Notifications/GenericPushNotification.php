<?php


namespace App\Filament\Plugins\Pwa\Notifications;


use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;


class GenericPushNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['webpush'];
    }


    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('New Notification')
            ->body('You have a new message')
            ->icon('/images/icons/icon-192.png')
            ->data(['url' => '/admin']);
    }
}