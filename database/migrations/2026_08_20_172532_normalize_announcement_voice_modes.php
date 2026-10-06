<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('announcement_configs')
            ->whereIn('voice_mode', ['offline', 'tts'])
            ->update(['voice_mode' => 'offline_tts']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('announcement_configs')
            ->whereIn('voice_mode', ['offline_tts', 'online_tts'])
            ->update(['voice_mode' => 'tts']);
    }
};
