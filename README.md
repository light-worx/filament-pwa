# Filament PWA

Progressive Web App support for Filament 5 (and 4) — turn your Laravel app into an
installable, app-like PWA with push notifications, SMS phone verification, a
messaging inbox, and a configurable navigation shell.

## Features

- **Installable PWA** — manifest, service worker, and install prompt out of the box.
- **Web push notifications** — send from your backend via a simple facade
  (`PushNotification::toPhone(...)`), built on `laravel-notification-channels/webpush`.
- **SMS phone verification** — 4-digit PIN flow (via BulkSMS by default) used to link a
  device to a person, so preferences and messages follow the person, not the device.
- **Shared device identity** — a `UserDevice` ↔ `UserPreference` model means a person
  who installs the app on two devices shares one identity, one settings set, and one inbox.
- **Messages inbox** — a simple in-app messaging system with reply-by-push support.
- **Configurable navigation** — top nav, bottom toolbar, and a right-hand user settings
  panel, all driven from `config/pwa.php`.
- **Dynamic select fields** — register resolvers to populate user-setting dropdowns from
  your own Eloquent models, with optional AJAX search for large lists.
- **Profile pictures** — upload/replace/remove, stored on any configured filesystem disk.
- **Flexible routing** — mount PWA routes under a path prefix, a subdomain, or the domain root.

## Requirements

- PHP ^8.3
- `filament/filament` ^4.0 or ^5.0
- `laravel-notification-channels/webpush` ^10.4

## Installation

```bash
composer require light-worx/filament-pwa
```

The package depends on `laravel-notification-channels/webpush`, which ships its own
`push_subscriptions` migration. Make sure that package's migrations have been run before
installing this one.

Then run the installer, which checks prerequisites, publishes assets/config, runs
migrations, and downloads the country-flag icons used by the phone picker:

```bash
php artisan filament-pwa:install
```

Alternatively, install manually:

```bash
php artisan vendor:publish --tag=filament-pwa-config
php artisan vendor:publish --tag=filament-pwa-assets
php artisan migrate
php artisan pwa:download-flags
```

Available publish tags:

| Tag                       | What it publishes                                              |
|---------------------------|------------------------------------------------------------------|
| `filament-pwa-config`     | `config/pwa.php`                                                 |
| `filament-pwa-assets`     | `public/service-worker.js`, `public/pwa/*`                       |
| `filament-pwa-views`      | Blade views to `resources/views/vendor/pwa` (for overriding)     |

## Usage

### 1. Register the middleware

Add `PwaDeviceMiddleware` to your `web` middleware group so every request has access to
the current device and its linked person-level preference.

Laravel 11+ (`bootstrap/app.php`):

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \Lightworx\FilamentPwa\Http\Middleware\PwaDeviceMiddleware::class,
    ]);
})
```

Laravel 10 (`app/Http/Kernel.php`, `web` group):

```php
\Lightworx\FilamentPwa\Http\Middleware\PwaDeviceMiddleware::class,
```

This makes the following available in controllers and Blade views:

```php
$request->pwaDevice;      // UserDevice|null for this browser
$request->pwaPreference;  // UserPreference|null (shared across a person's devices)
$request->pwaPhone;       // verified phone number, or null
```

```blade
@if($pwaPreference)
    Welcome, {{ $pwaPreference->name }}
@endif
```

### 2. Add manifest and service worker registration to your layout

```html
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="{{ config('pwa.theme.theme_color') }}">

<script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js');
  }
</script>
```

### 3. Register the Filament plugin (optional)

If you want the PWA install prompt and styling injected into a Filament panel:

```php
use Lightworx\FilamentPwa\FilamentPwaPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(FilamentPwaPlugin::make());
}
```

### 4. Configure `config/pwa.php`

Key sections you'll typically want to adjust:

- **App identity** — `app_name`, `app_short`, `description`.
- **Routing** — `route_prefix` / `route_domain`, e.g. mount at `/app`, at `app.yoursite.com`,
  or the domain root.
- **Theme** — colours emitted as CSS custom properties for the toolbar, bottom nav, and body.
- **`nav_items` / `bottom_items`** — the left slide menu and bottom toolbar entries.
- **`user_fields`** — extra fields shown in the right-hand user settings panel
  (`text`, `email`, `tel`, `number`, `select`, `toggle`), including static or dynamic options.
- **`identity`** — the Eloquent model used to resolve a display name (and optional picture)
  from a verified phone number.
- **`sms`** — SMS driver configuration (BulkSMS by default).
- **`push`** and **`vapid`** — push notification toggles and VAPID keys.
- **`picture_upload`** — disk/path for profile picture uploads.

See the fully-commented `src/Config/pwa.php` for every option and example.

## Sending push notifications

```php
use Lightworx\FilamentPwa\Facades\PushNotification;

// Basic
PushNotification::toPhone('+27820000000', 'Order ready', 'Your order #1042 is ready.');

// With a deep link and extra options
PushNotification::toPhone(
    '+27820000000',
    'Order ready',
    'Tap to view your order.',
    '/orders/1042',
    ['tag' => 'order-1042']
);

// Multiple recipients or a full broadcast
PushNotification::toPhones(['+27820000000', '+27823456789'], 'Maintenance', 'Offline at 22:00.');
PushNotification::broadcast('Scheduled maintenance', 'Site goes offline at midnight.');
```

Every send returns a `SendResult` with `sent`, `failed`, `stale`, `noDevices`, `total()`,
and `allDelivered()`. See [`docs/push-notifications.md`](docs/push-notifications.md) for the
full API, queueing example, and the notification payload shape expected by the service worker.

## Dynamic user-field options

Populate a `select` field in the user settings panel from your own data:

```php
// AppServiceProvider::boot()
use Lightworx\FilamentPwa\Facades\PwaFieldOptions;

PwaFieldOptions::register('region', fn () =>
    Region::orderBy('name')->pluck('name', 'id')->toArray()
);
```

```php
// config/pwa.php
'user_fields' => [
    ['type' => 'select', 'key' => 'region', 'label' => 'Region', 'options' => 'dynamic'],
],
```

Supports closure or class-based resolvers, and an AJAX-searchable mode for large lists.
Full details, including the class-based resolver interface and the field-options endpoint
response shape, are in [`docs/dynamic-field-options.md`](docs/dynamic-field-options.md).

## Artisan commands

| Command                          | Description                                                        |
|-----------------------------------|----------------------------------------------------------------------|
| `filament-pwa:install`            | Checks prerequisites, publishes assets/config, runs migrations, downloads flags. |
| `pwa:download-flags [--force]`    | Downloads the country-flag PNGs used by the phone-number country picker. |

## How device and identity linking works

- A **`UserDevice`** row is created for each browser/installation (keyed by a device id,
  later promoted to the browser's push endpoint once push is granted).
- A **`UserPreference`** row represents a *person*, holding their verified phone number,
  resolved display name/picture, and custom settings.
- SMS verification links a `UserDevice` to a `UserPreference`. From that point on, settings,
  messages, and push subscriptions for that person are shared across every device they've
  verified — install the app on a phone and a tablet, verify the same number on both, and
  both stay in sync.
- If `pwa.identity.model` is configured, the linked person's display name (and optionally
  profile picture) are resolved automatically from your own Eloquent model by matching the
  verified phone number.

## Documentation

- [`docs/push-notifications.md`](docs/push-notifications.md) — sending notifications, `SendResult`, queueing, Filament Action integration.
- [`docs/dynamic-field-options.md`](docs/dynamic-field-options.md) — dynamic/searchable select fields in the user settings panel.

## License

MIT — see [`LICENSE.md`](LICENSE.md).