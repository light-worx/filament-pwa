<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('push_subscriptions')) {
            throw new \RuntimeException(
                'The push_subscriptions table does not exist. ' .
                'Please ensure laravel-notification-channels/webpush is installed ' .
                'and its migrations have been run before running filament-pwa migrations.'
            );
        }

        // Only add the column if it isn't already there (makes migration re-runnable)
        if (Schema::hasColumn('push_subscriptions', 'user_preference_id')) {
            return;
        }

        Schema::table('push_subscriptions', function ($table) {
            $table->foreignId('user_preference_id')
                  ->nullable()
                  ->constrained('user_preferences')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('push_subscriptions')) return;
        if (!Schema::hasColumn('push_subscriptions', 'user_preference_id')) return;

        Schema::table('push_subscriptions', function ($table) {
            $table->dropForeign(['user_preference_id']);
            $table->dropColumn('user_preference_id');
        });
    }
};