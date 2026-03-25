<?php

namespace Lightworx\FilamentPwa\Helpers;

use Lightworx\FilamentPwa\Models\UserPreference;

class UserPreferenceSettings
{
    protected UserPreference $preference;

    public function __construct(UserPreference $preference)
    {
        $this->preference = $preference;

        // Ensure custom_settings is always an array
        if (is_null($this->preference->custom_settings)) {
            $this->preference->custom_settings = [];
        }
    }

    /**
     * Get a setting by key.
     */
    public function get(string $key, $default = null)
    {
        return $this->preference->custom_settings[$key] ?? $default;
    }

    /**
     * Set a setting by key and save immediately.
     */
    public function set(string $key, $value): void
    {
        $settings = $this->preference->custom_settings;
        $settings[$key] = $value;
        $this->preference->custom_settings = $settings;
        $this->preference->save();
    }

    /**
     * Remove a setting by key.
     */
    public function forget(string $key): void
    {
        $settings = $this->preference->custom_settings;
        if (isset($settings[$key])) {
            unset($settings[$key]);
            $this->preference->custom_settings = $settings;
            $this->preference->save();
        }
    }

    /**
     * Return all custom settings as an array.
     */
    public function all(): array
    {
        return $this->preference->custom_settings;
    }
}