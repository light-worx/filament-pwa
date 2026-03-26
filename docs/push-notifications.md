# Sending Push Notifications

The package ships a `PushNotificationService` and a `PushNotification` facade for sending
notifications from your Laravel backend code.

## Setup

Add the facade alias to `config/app.php` (optional — Laravel auto-discovers it, but explicit
aliases are clearer):

```php
'aliases' => [
    // ...
    'PushNotification' => \Lightworx\FilamentPwa\Facades\PushNotification::class,
],
```

## Sending by phone number

The most common use-case: send a notification to whoever registered the given mobile number.

```php
use Lightworx\FilamentPwa\Facades\PushNotification;

// Basic — title and body only
PushNotification::toPhone('+27820000000', 'Order ready', 'Your order #1042 is ready to collect.');

// With a deep-link URL (opens this page when notification is tapped)
PushNotification::toPhone('+27820000000', 'Order ready', 'Tap to view your order.', '/orders/1042');

// With extra options (overrides service-worker defaults)
PushNotification::toPhone('+27820000000', 'Order ready', 'Tap to view.', '/orders/1042', [
    'icon'  => '/icons/order-icon.png',
    'badge' => '/icons/badge.png',
    'tag'   => 'order-1042',   // replaces any previous notification with this tag
]);
```

## Sending to multiple phones

```php
PushNotification::toPhones(
    ['+27820000000', '+27823456789'],
    'System maintenance',
    'The system will be offline from 22:00–23:00 tonight.'
);
```

## Sending to a UserPreference directly

Useful when you've already looked up the preference via your own query:

```php
use Lightworx\FilamentPwa\Models\UserPreference;

$pref = UserPreference::where('email', 'jane@example.com')->first();

if ($pref) {
    PushNotification::toPreference($pref, 'Hello Jane', 'You have a new message.');
}
```

## Broadcast to all devices

```php
PushNotification::broadcast('Scheduled maintenance', 'Site goes offline at midnight.');
```

Use sparingly — this sends to every subscribed device regardless of phone verification.

## Handling the result

Every method returns a `SendResult` value object:

```php
$result = PushNotification::toPhone('+27820000000', 'Test', 'Hello');

$result->sent;           // int  — successfully delivered
$result->failed;         // int  — delivery failures
$result->stale;          // array — endpoints deleted (410 Gone responses)
$result->noDevices;      // bool — true when no subscriptions were found
$result->total();        // int  — sent + failed
$result->allDelivered(); // bool — no failures

if ($result->noDevices) {
    // Phone number not registered / not verified
}

if (!$result->allDelivered()) {
    Log::warning('Some push deliveries failed', $result->toArray());
}
```

## Using from a Filament Action

```php
use Lightworx\FilamentPwa\Facades\PushNotification;

Action::make('notify')
    ->label('Send notification')
    ->action(function (Model $record) {
        $result = PushNotification::toPhone(
            $record->mobile,
            'Update from ' . config('app.name'),
            'Your record has been updated.',
            '/records/' . $record->id
        );

        if ($result->noDevices) {
            Notification::make()->warning()->title('No registered devices for this number')->send();
        } elseif ($result->allDelivered()) {
            Notification::make()->success()->title('Notification sent')->send();
        } else {
            Notification::make()->danger()->title('Delivery failed for some devices')->send();
        }
    });
```

## Queueing notifications

For high-volume sends, wrap the call in a queued job:

```php
// app/Jobs/SendPushJob.php
class SendPushJob implements ShouldQueue
{
    public function __construct(
        public string $phone,
        public string $title,
        public string $body,
    ) {}

    public function handle(PushNotificationService $push): void
    {
        $push->toPhone($this->phone, $this->title, $this->body);
    }
}

// Dispatch:
SendPushJob::dispatch('+27820000000', 'Hello', 'World');
```

## Notification payload

The service worker expects this JSON structure (all fields optional except `title`):

```json
{
    "title": "Notification title",
    "body":  "Notification body text",
    "url":   "/path/to/open/on/click",
    "icon":  "/pwa/icons/icon-192.png",
    "badge": "/pwa/icons/badge-72.png",
    "tag":   "unique-tag-replaces-previous"
}
```

Any keys you pass in the `$extra` array are merged into this payload and will be available
in `event.notification.data` inside the service worker if you need custom behaviour.