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
            $table->decimal('tts_speed', 3, 2)->default(1)->after('tts_language');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcement_configs', function (Blueprint $table) {
            $table->dropColumn('tts_speed');
        });
    }
};
