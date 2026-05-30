<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the push_subscriptions table created by laravel-notification-channels/webpush
 * to link each subscription to a specific UserDevice rather than to a UserPreference.
 *
 * This means one push subscription per device per person — correct granularity
 * for a device-keyed push system. Fan-out to all of a person's devices is handled
 * by loading all UserDevice rows for a UserPreference and dispatching from there.
 *
 * Requires:
 *   - laravel-notification-channels/webpush installed and migrated first
 *   - user_devices table already created (run after 000002)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('push_subscriptions')) {
            throw new \RuntimeException(
                'The push_subscriptions table does not exist. ' .
                'Please ensure laravel-notification-channels/webpush is installed ' .
                'and its migrations have been run before running filament-pwa migrations.'
            );
        }

        // Idempotent — safe to re-run if migration was interrupted
        if (Schema::hasColumn('push_subscriptions', 'user_device_id')) {
            return;
        }

        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->foreignId('user_device_id')
                  ->nullable()
                  ->constrained('user_devices')
                  ->cascadeOnDelete()
                  ->comment('The specific device that owns this subscription');

            // The webpush package creates these as non-nullable morphs by default;
            // we null them out since we are using our own device-based relationship
            // instead of Laravel's notifiable pattern.
            if (Schema::hasColumn('push_subscriptions', 'subscribable_type')) {
                $table->string('subscribable_type')->nullable()->change();
            }

            if (Schema::hasColumn('push_subscriptions', 'subscribable_id')) {
                $table->unsignedBigInteger('subscribable_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('push_subscriptions')) {
            return;
        }

        Schema::table('push_subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('push_subscriptions', 'user_device_id')) {
                $table->dropForeign(['user_device_id']);
                $table->dropColumn('user_device_id');
            }
        });
    }
};