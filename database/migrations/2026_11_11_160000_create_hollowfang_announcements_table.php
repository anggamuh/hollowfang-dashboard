<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hollowfang_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('channel_id', 32);
            $table->string('title', 256);
            $table->boolean('successful')->default(false);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('discord_message_id', 32)->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('hollowfang_announcements');
    }
};
