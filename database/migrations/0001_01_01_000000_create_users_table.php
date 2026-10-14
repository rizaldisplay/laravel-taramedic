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

            $table->string('phone_number', 20)->unique();

            $table->boolean('phone_verified')->default(false);

            $table->timestamp('phone_verified_at')->nullable();

            $table->string('password');

            $table->enum('role', [
                'super_admin',
                'admin',
                'doctor',
                'nurse',
                'pharmacist',
                'registration',
                'cashier',
                'laboratory'
            ]);

            $table->boolean('two_factor_enabled')->default(true);

            $table->timestamps();
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

        // database/migrations/xxxx_create_trusted_devices_table.php
        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('device_id')->index(); // Hash unik identifier dari client/device
            $table->string('device_name');       // Misal: "Chrome di Windows", "App Android"
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->boolean('is_trusted')->default(true);
            $table->timestamp('last_active_at');
            $table->timestamps();
        });

        // database/migrations/xxxx_create_audit_logs_table.php
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action');           // Misal: 'auth.login', 'rekam_medis.create'
            $table->string('description');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('payload')->nullable(); // Menampung detail data terkait
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
