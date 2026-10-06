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
        DB::table('announcement_configs')->update(['cue_gap_ms' => 0]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The previous per-row delay cannot be reconstructed safely.
    }
};
