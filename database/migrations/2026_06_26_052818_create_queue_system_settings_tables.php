<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->default('general')->index();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('announcement_configs', function (Blueprint $table) {
            $table->id();
            $table->string('tts_language', 10)->default('id-ID');
            $table->text('tts_template')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_configs');
        Schema::dropIfExists('settings');
    }
};
