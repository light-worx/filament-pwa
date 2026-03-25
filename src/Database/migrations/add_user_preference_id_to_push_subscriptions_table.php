<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_subscriptions', function ($table) {
            $table->foreignId('user_preference_id')
                  ->nullable()
                  ->constrained('user_preferences')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('push_subscriptions', function ($table) {
            $table->dropForeign(['user_preference_id']);
            $table->dropColumn('user_preference_id');
        });
    }
};