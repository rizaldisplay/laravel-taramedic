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
            $table->dropColumn([
                'number_mode',
                'queue_label_enabled',
                'destination_enabled',
                'counter_label_enabled',
                'clip_pause_ms',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcement_configs', function (Blueprint $table) {
            $table->string('number_mode', 20)->default('digits')->after('repeat_count');
            $table->boolean('queue_label_enabled')->default(true)->after('opening_enabled');
            $table->boolean('destination_enabled')->default(true)->after('queue_label_enabled');
            $table->boolean('counter_label_enabled')->default(true)->after('destination_enabled');
            $table->unsignedSmallInteger('clip_pause_ms')->default(100)->after('closing_enabled');
        });
    }
};
