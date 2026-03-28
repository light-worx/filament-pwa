<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function ($table) {
            $table->id();
            $table->string('device_id')->nullable()->unique()->comment('Push endpoint or local UUID');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->json('custom_settings')->nullable()->comment('Developer-defined extra fields');
            $table->string('email_verification_pin', 4)->nullable();
            $table->timestamp('pin_expires_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('phone_verified')->default(false);
            $table->boolean('preaching_reminders')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};