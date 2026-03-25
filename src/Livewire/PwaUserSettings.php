<?php

namespace Lightworx\FilamentPwa\Livewire;

use Lightworx\FilamentPwa\Helpers\UserPreferenceSettings;
use Livewire\Component;
use Lightworx\FilamentPwa\Models\PushSubscription;
use Lightworx\FilamentPwa\Models\UserPreference;

class PwaUserSettings extends Component
{
    public $device_id;

    public $name;
    public $email;
    public $phone;
    public $custom_settings_json = '{}'; // Bound to textarea

    protected $preference;
    protected $settingsHelper;

    public function mount($device_id)
    {
        $this->device_id = $device_id;

        // Find or create preference record for this device
        $this->preference = UserPreference::firstOrCreate(
            ['device_id' => $device_id]
        );

        $this->settingsHelper = new UserPreferenceSettings($this->preference);

        $this->name = $this->preference->name;
        $this->email = $this->preference->email;
        $this->phone = $this->preference->phone;
        $this->custom_settings_json = json_encode($this->settingsHelper->all(), JSON_PRETTY_PRINT);
    }

    /**
     * Save user info and custom settings
     */
    public function save()
    {
        $this->preference->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
        ]);

        // Validate JSON
        $decoded = json_decode($this->custom_settings_json, true);
        if ($decoded === null && $this->custom_settings_json !== '{}') {
            $this->dispatch('notify', [
                'type' => 'danger',
                'message' => 'Custom settings JSON is invalid',
            ]);
            return;
        }

        // Update custom_settings via helper
        $this->settingsHelper->preference->custom_settings = $decoded ?? [];
        $this->settingsHelper->preference->save();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Preferences saved successfully',
        ]);
    }

    /**
     * Save push subscription linked to this preference
     */
    public function savePushSubscription($endpoint, $keys)
    {
        if (isset($this->preference->id)){
            PushSubscription::updateOrCreate(
                ['endpoint' => $endpoint],
                [
                    'keys' => $keys,
                    'user_preference_id' => $this->preference->id,
                ]
            );
        }
    }

    public function render()
    {
        return view('filament-pwa::livewire.pwa-user-settings');
    }
}