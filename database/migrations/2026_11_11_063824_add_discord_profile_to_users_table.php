<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'discord_username')) {
                $table->string('discord_username', 255)
                    ->nullable();
            }

            if (! Schema::hasColumn('users', 'discord_avatar')) {
                $table->text('discord_avatar')
                    ->nullable();
            }
        });
    }

    public function down(): void
    {
        // Kolom mungkin sudah ada sebelum migration ini.
        // Jangan hapus otomatis agar data Discord tetap aman.
    }
};
