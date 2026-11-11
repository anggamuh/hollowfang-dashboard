<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hollowfang_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('channel_name', 100)->nullable();
            $table->text('webhook_url');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('hollowfang_webhook_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->nullable()
                ->constrained('hollowfang_webhooks')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('title', 256)->nullable();
            $table->boolean('successful');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hollowfang_webhook_dispatches');
        Schema::dropIfExists('hollowfang_webhooks');
    }
};
