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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique();
            $table->string('name', 100);
            $table->string('email')->unique();
            $table->string('phone_number', 20)->unique()->nullable();
            $table->boolean('phone_verified')->default(false);
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('password');
            
            // [BARU] Relasi ke Identitas SDMK Aktif
            $table->unsignedBigInteger('sdmk_id')->nullable()->comment('Relasi ke tabel master SDMK');

            $table->boolean('two_factor_enabled')->default(true);
            $table->timestamps();
        });

        // [BARU] Tabel Master Peran (Roles)
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Contoh: "Petugas Pendaftaran"
            $table->string('slug')->unique(); // Contoh: "petugas_pendaftaran"
            $table->timestamps();
        });

        // [BARU] Tabel Pivot Many-to-Many antara Users dan Roles
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            
            // Primary key gabungan agar tidak ada duplikasi data
            $table->primary(['user_id', 'role_id']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('token', 255)->unique();
            $table->string('device_id', 100);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_revoked')->default(false);
            $table->timestamps();
        });

        Schema::create('user_sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('device_id', 100);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
        });

        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('device_id')->index();
            $table->string('device_name');
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->boolean('is_trusted')->default(true);
            $table->timestamp('last_active_at');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action');
            $table->string('description');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Urutan drop harus dari tabel yang memiliki foreign key ke tabel master
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('trusted_devices');
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('refresh_tokens');
        Schema::dropIfExists('role_user'); // Drop pivot table
        Schema::dropIfExists('roles');     // Drop master roles table
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};