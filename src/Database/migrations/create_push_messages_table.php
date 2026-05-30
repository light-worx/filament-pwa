<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail of push notifications sent.
 *
 * One row per notification dispatched to a UserPreference (person).
 * Because settings and identity live on UserPreference, this record
 * ties the message to the person regardless of how many devices they have.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_messages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->string('sender_name')->nullable();
            $table->string('sender_phone')->nullable();
            $table->foreignId('user_preference_id')
                  ->constrained('user_preferences')
                  ->cascadeOnDelete();
            $table->boolean('seen')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_messages');
    }
};