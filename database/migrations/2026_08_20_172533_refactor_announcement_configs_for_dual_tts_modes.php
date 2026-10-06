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
            $table->string('voice_mode', 20)->default('offline_tts')->change();
            $table->unsignedSmallInteger('cue_gap_ms')->default(0)->after('closing_enabled');
            $table->string('tts_style', 20)->default('natural')->after('tts_speed');
            $table->decimal('online_pitch', 3, 2)->default(1)->after('tts_style');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcement_configs', function (Blueprint $table) {
            $table->string('voice_mode', 20)->default('offline')->change();
            $table->dropColumn(['cue_gap_ms', 'tts_style', 'online_pitch']);
        });
    }
};
