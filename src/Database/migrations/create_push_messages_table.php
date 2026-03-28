<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_messages', function ($table) {
            $table->id();
            $table->text('message');
            $table->string('title');
            $table->string('sender_name')->nullable();
            $table->string('sender_phone')->nullable();
            $table->string('user_preference_id');
            $table->boolean('seen')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_messages');
    }
};