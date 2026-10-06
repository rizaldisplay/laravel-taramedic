<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('announcement_configs', function (Blueprint $table) {
            $table->string('voice_mode', 20)->default('offline')->after('id');
            $table->boolean('announcement_enabled')->default(true)->after('voice_mode');
            $table->unsignedTinyInteger('audio_volume')->default(100)->after('announcement_enabled');
            $table->unsignedTinyInteger('repeat_count')->default(1)->after('audio_volume');
            $table->string('number_mode', 20)->default('digits')->after('repeat_count');
            $table->boolean('opening_enabled')->default(true)->after('number_mode');
            $table->boolean('queue_label_enabled')->default(true)->after('opening_enabled');
            $table->boolean('destination_enabled')->default(true)->after('queue_label_enabled');
            $table->boolean('counter_label_enabled')->default(true)->after('destination_enabled');
            $table->boolean('closing_enabled')->default(true)->after('counter_label_enabled');
            $table->unsignedSmallInteger('clip_pause_ms')->default(100)->after('closing_enabled');
            $table->unsignedSmallInteger('repeat_pause_ms')->default(800)->after('clip_pause_ms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcement_configs', function (Blueprint $table) {
            $table->dropColumn([
                'voice_mode',
                'announcement_enabled',
                'audio_volume',
                'repeat_count',
                'number_mode',
                'opening_enabled',
                'queue_label_enabled',
                'destination_enabled',
                'counter_label_enabled',
                'closing_enabled',
                'clip_pause_ms',
                'repeat_pause_ms',
            ]);
        });
    }
};
