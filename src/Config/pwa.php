<?php

return [

    /*
    |--------------------------------------------------------------------------
    | App Identity
    |--------------------------------------------------------------------------
    */
    'app_name'    => env('PWA_APP_NAME', env('APP_NAME', 'My App')),
    'app_short'   => env('PWA_APP_SHORT', 'App'),
    'description' => env('PWA_DESCRIPTION', 'A progressive web application'),

    /*
    |--------------------------------------------------------------------------
    | Routing
    |
    | prefix  — the URL path segment (or subdomain label) for all PWA routes.
    |           Default: 'app'  →  your-site.com/app/messages etc.
    |           Change to e.g. 'pwa' for your-site.com/pwa/messages.
    |           Set to '' (empty string) to mount routes at the domain root.
    |
    | domain  — when set, routes are mounted on a subdomain instead of a path
    |           prefix. Set to the subdomain label only, not the full domain.
    |           e.g. 'app' makes routes live at  app.your-site.com/messages
    |           Leave null (default) to use path-prefix mode.
    |
    | Examples:
    |   Path prefix:   prefix='app', domain=null  → site.com/app/subscribe
    |   Sub-domain:    prefix='app', domain='app' → app.site.com/subscribe
    |   Custom prefix: prefix='pwa', domain=null  → site.com/pwa/subscribe
    |   Root mount:    prefix='',    domain=null  → site.com/subscribe
    |--------------------------------------------------------------------------
    */
    'route_prefix' => env('PWA_ROUTE_PREFIX', 'app'),
    'route_domain' => env('PWA_ROUTE_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Theme colours — emitted as CSS custom properties on :root
    |--------------------------------------------------------------------------
    */
    'theme' => [
        'primary'      => env('PWA_COLOR_PRIMARY',    '#1f2937'),
        'accent'       => env('PWA_COLOR_ACCENT',     '#3b82f6'),
        'toolbar_bg'   => env('PWA_TOOLBAR_BG',       '#ffffff'),
        'toolbar_text' => env('PWA_TOOLBAR_TEXT',     '#111827'),
        'bottom_bg'    => env('PWA_BOTTOM_BG',        '#1f2937'),
        'bottom_text'  => env('PWA_BOTTOM_TEXT',      '#cbd5e1'),
        'bottom_active'=> env('PWA_BOTTOM_ACTIVE',    '#ffffff'),
        'body_bg'      => env('PWA_BODY_BG',          '#f5f6f8'),
        'theme_color'  => env('PWA_THEME_COLOR',      '#1f2937'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation — items rendered in the left slide menu
    |--------------------------------------------------------------------------
    */
    'nav_items' => [
        ['label' => 'Home', 'icon' => 'bi-house', 'route' => 'app.home'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bottom toolbar items
    |--------------------------------------------------------------------------
    */
    /*
    |--------------------------------------------------------------------------
    | Bottom toolbar items
    |
    | Each item supports:
    |   icon    => Bootstrap Icons class (bi-*)
    |   label   => short text below icon
    |   route   => named Laravel route  (preferred)
    |   url     => absolute or relative URL
    |   badge   => 'messages' to show the live unread count bubble,
    |              or any other string key for future badge types
    |--------------------------------------------------------------------------
    */
    'bottom_items' => [
        ['icon' => 'bi-house',          'route' => 'app.home',       'label' => 'Home'],
        ['icon' => 'bi-chat-left-text',  'route' => 'app.messages',   'label' => 'Messages', 'badge' => 'messages'],
    ],

    /*
    |--------------------------------------------------------------------------
    | User settings — extra fields rendered in the right slide-over panel
    |
    | Field types: text | email | tel | number | select | toggle
    |
    | For 'select', the 'options' key can be:
    |
    |   A static array:
    |     'options' => ['red' => 'Red', 'blue' => 'Blue']
    |
    |   'dynamic' — resolved at render time by a registered resolver.
    |     Register in AppServiceProvider::boot():
    |       PwaFieldOptions::register('region', fn() =>
    |           Region::orderBy('name')->pluck('name', 'id')->toArray()
    |       );
    |
    |   'dynamic' + 'searchable' => true — same resolver, but options are
    |     fetched via AJAX when the panel opens (and re-fetched as the user
    |     types). Good for large lists (100+ items). The resolver receives
    |     the search string as its first argument:
    |       PwaFieldOptions::register('product', fn(?string $search) =>
    |           Product::when($search, fn($q) => $q->where('name','like',"%{$search}%"))
    |                   ->limit(50)->pluck('name','id')->toArray()
    |       );
    |--------------------------------------------------------------------------
    */
    'user_fields' => [
        // Static list
        // ['type' => 'select', 'key' => 'colour', 'label' => 'Favourite colour',
        //  'options' => ['red' => 'Red', 'green' => 'Green', 'blue' => 'Blue']],

        // Dynamic (resolver called at render time, options embedded in HTML)
        // ['type' => 'select', 'key' => 'region', 'label' => 'Region',
        //  'options' => 'dynamic'],

        // Dynamic + searchable (AJAX, supports large lists)
        // ['type' => 'select', 'key' => 'product', 'label' => 'Product',
        //  'options' => 'dynamic', 'searchable' => true,
        //  'placeholder' => 'Search products…'],

        // Other field types
        // ['type' => 'text',   'key' => 'department', 'label' => 'Department'],
        // ['type' => 'toggle', 'key' => 'dark_mode',  'label' => 'Dark mode'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Phone country codes
    |
    | Leave empty [] to show ALL countries.
    | Specify an array of ISO 3166-1 alpha-2 codes to restrict the list.
    | The first entry in the list (or 'ZA' if unset) is the default.
    | Example: ['ZA', 'GB', 'US', 'AU', 'NZ', 'KE', 'NG']
    |--------------------------------------------------------------------------
    */
    'phone_countries' => [],           // empty = all countries
    'phone_default_country' => 'ZA',   // ISO code for the pre-selected country

    /*
    |--------------------------------------------------------------------------
    | SMS verification
    |
    | driver:   'bulksms' (default) — add more drivers in SmsService.php
    | bulksms:  API token ID/secret from https://www.bulksms.com
    |   from:   optional sender ID (max 11 alphanumeric chars)
    |--------------------------------------------------------------------------
    */
    'sms' => [
        'driver'  => env('PWA_SMS_DRIVER', 'bulksms'),
        'bulksms' => [
            'username' => env('PWA_BULKSMS_USERNAME', ''),
            'password' => env('PWA_BULKSMS_PASSWORD', ''),
            'from'     => env('PWA_BULKSMS_FROM', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Identity resolution
    |
    | After a phone number is verified, the package looks up the user's name
    | from your app's own model. Configure the model class, the field that
    | holds the phone number (must be stored in E.164: +27820000000), and
    | the field (or dot-notation path) that holds the display name.
    |
    | not_found_message: shown in the user panel when the phone number is
    |   verified but no matching record exists in your model.
    |--------------------------------------------------------------------------
    */
    'identity' => [
        // Eloquent model to look up by verified phone number.
        // Set to e.g. App\Models\Member::class (or via .env as a string class name).
        'model'       => env('PWA_IDENTITY_MODEL', ''),
        'phone_field' => env('PWA_IDENTITY_PHONE', 'phone'),
        'name_field'  => env('PWA_IDENTITY_NAME',  'name'),   // dot-notation supported

        // Dot-notation path on the identity model for a profile picture URL/path.
        // Leave null if your model has no picture. Example: 'profile_picture'
        // or 'avatar.url' for a related model.
        // The value is treated as a URL if it starts with http, otherwise
        // passed through asset() to build a public URL.
        'picture_field' => env('PWA_IDENTITY_PICTURE', null),

        // When true, the SMS PIN is only sent if the phone number already exists
        // in the model above. Unknown numbers receive a 403 and no SMS is sent.
        // When false, any number can register; name lookup is enrichment only.
        'require_known_number' => env('PWA_REQUIRE_KNOWN_NUMBER', true),

        // Message returned to the user when their number is not found.
        // Used both as the 403 response body (require_known_number=true)
        // and as the warning shown in the panel after verification (=false).
        'unknown_message' => env('PWA_IDENTITY_NOT_FOUND',
            'This number is not registered on this site.'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Push notifications
    |--------------------------------------------------------------------------
    */
    'push' => [
        'enabled'           => env('PWA_PUSH_ENABLED', true),
        'prompt_on_install' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Profile picture uploads
    |
    | When a user uploads or takes a new profile picture via the PWA panel,
    | it is stored using Laravel's filesystem. Configure the disk and the
    | directory within that disk.
    |
    | disk — any disk defined in config/filesystems.php (default: 'public')
    | path — subdirectory within the disk (default: 'pwa/avatars')
    |
    | The uploaded image is served via Storage::url() so ensure the disk
    | is publicly accessible (run 'php artisan storage:link' for the public disk).
    |--------------------------------------------------------------------------
    */
    'picture_upload' => [
        // Filesystem disk to store uploaded pictures on (must be publicly accessible).
        // For the 'public' disk run: php artisan storage:link
        // The same disk is used to resolve display URLs for pictures stored as
        // bare filenames/paths in the identity model's picture_field.
        'disk'   => env('PWA_PICTURE_DISK', 'public'),
        'path'   => env('PWA_PICTURE_PATH', 'pwa/avatars'),
        'max_kb' => env('PWA_PICTURE_MAX_KB', 2048),
    ],

    /*
    |--------------------------------------------------------------------------
    | Install prompt
    |--------------------------------------------------------------------------
    */
    'install_prompt' => env('PWA_INSTALL_PROMPT', true),

    /*
    |--------------------------------------------------------------------------
    | Push notification icons
    |
    | icon  — shown as the notification image (192×192px recommended).
    |         Defaults to /pwa/icons/icon-192.png
    | badge — small monochrome icon shown in the status bar on Android (72×72px).
    |         Defaults to /pwa/icons/badge-72.png
    |
    | Set these to paths within your public directory, e.g.:
    |   'icon'  => '/images/notification-icon.png',
    |   'badge' => '/images/notification-badge.png',
    |--------------------------------------------------------------------------
    */
    'push_icon'  => env('PWA_PUSH_ICON',  '/pwa/icons/icon-192.png'),
    'push_badge' => env('PWA_PUSH_BADGE', '/pwa/icons/badge-72.png'),

];