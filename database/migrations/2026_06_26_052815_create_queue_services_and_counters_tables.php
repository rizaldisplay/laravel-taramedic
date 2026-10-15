<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TIPE_PENDAFTARAN = 'Ruang Pendaftaran';

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
            $table->string('code', 30)->unique();
            $table->string('name', 100)->unique();
            $table->string('tipe', 30)->index();
            $table->string('deskripsi', 255)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('claimed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
        });

        $this->backfill();

        Schema::create('antreans', function (Blueprint $table) {
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
            $table->foreignId('antrean_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['counter_id', 'service_id', 'antrean_id']);
        });
    }

        private function backfill(): void
    {
        $names = [];
        foreach ((array) config('registration.counters', []) as $id => $name) {
            $names[(int) $id] = (string) $name;
        }

        if (Schema::hasTable('visits') && Schema::hasColumn('visits', 'counter_number')) {
            foreach (DB::table('visits')->distinct()->pluck('counter_number') as $id) {
                $names[(int) $id] ??= 'Loket ' . (int) $id;
            }
        }

        if ($names === []) {
            return;
        }

        ksort($names);
        $dipakai = [];
        $now = now();

        foreach ($names as $id => $name) {
            if ($id < 1) {
                continue;
            }
            // Nama harus unik; bila config memuat nama ganda, beri nomor.
            $unik = $name;
            if (isset($dipakai[mb_strtolower($unik)])) {
                $unik = $name . ' (' . $id . ')';
            }
            $dipakai[mb_strtolower($unik)] = true;

            DB::table('counters')->insert([
                'id' => $id,
                'code' => 'LOKET-' . $id,
                'name' => $unik,
                'tipe' => self::TIPE_PENDAFTARAN,
                'deskripsi' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // PostgreSQL: id eksplisit tidak memajukan sequence, sehingga insert berikutnya bisa bentrok.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('counters', 'id'), (SELECT MAX(id) FROM counters))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counter_service');
        Schema::dropIfExists('antrean');
        Schema::dropIfExists('counters');
        Schema::dropIfExists('services');
    }
};
