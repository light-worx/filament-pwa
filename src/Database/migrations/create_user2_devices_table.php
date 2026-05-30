<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per registered browser / app instance.
 *
 * device_id is the push endpoint or local UUID that identifies the
 * specific device. Multiple devices can belong to the same
 * user_preferences row (person), which means:
 *
 *   - Settings changed on one device immediately apply everywhere.
 *   - A notification sent to a person reaches all their devices.
 *   - push_subscriptions links to this table (not to user_preferences),
 *     so each subscription is scoped to the exact device that created it.
 *
 * A device may exist before the user has verified a phone number, in
 * which case user_preference_id is null until verification completes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_preference_id')
                  ->nullable()
                  ->constrained('user_preferences')
                  ->nullOnDelete()
                  ->comment('Null until the device is linked to a verified phone number');
            $table->string('device_id')
                  ->unique()
                  ->comment('Push endpoint or local UUID identifying this browser/app instance');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};