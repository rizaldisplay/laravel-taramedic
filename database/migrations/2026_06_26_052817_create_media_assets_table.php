<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index();
            $table->string('name');
            $table->string('disk', 50)->default('public');
            $table->string('path');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
