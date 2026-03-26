<?php

return [

    /*
    |--------------------------------------------------------------------------
    | App Identity
    |--------------------------------------------------------------------------
    */
    'app_name'    => env('PWA_APP_NAME', env('APP_NAME', 'My App')),
    'app_route'   => '/app',
    'app_short'   => env('PWA_APP_SHORT', 'App'),
    'description' => env('PWA_DESCRIPTION', 'A progressive web application'),

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
    'bottom_items' => [
        ['icon' => 'bi-house',  'route' => 'app.home', 'label' => 'Home'],
        ['icon' => 'bi-search', 'url'   => '#',        'label' => 'Search'],
        ['icon' => 'bi-gear',   'url'   => '#',        'label' => 'Settings'],
    ],

    /*
    |--------------------------------------------------------------------------
    | User settings — extra fields rendered in the right slide-over panel
    |--------------------------------------------------------------------------
    */
    'user_fields' => [
        // ['type' => 'text',   'key' => 'department', 'label' => 'Department'],
        // ['type' => 'select', 'key' => 'region', 'label' => 'Region',
        //  'options' => ['za' => 'South Africa', 'uk' => 'United Kingdom']],
        // ['type' => 'toggle', 'key' => 'dark_mode', 'label' => 'Dark mode'],
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
    | Push notifications
    |--------------------------------------------------------------------------
    */
    'push' => [
        'enabled'           => env('PWA_PUSH_ENABLED', true),
        'prompt_on_install' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Install prompt
    |--------------------------------------------------------------------------
    */
    'install_prompt' => env('PWA_INSTALL_PROMPT', true),

];