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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->unsignedSmallInteger('estimated_minutes')->default(5);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('counters', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('claimed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('counter_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['counter_id', 'service_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counter_service');
        Schema::dropIfExists('counters');
        Schema::dropIfExists('services');
    }
};
