<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // ----------------------------------------------------
            // 1. SEED TABEL: roles
            // ----------------------------------------------------
            $roles = [
                ['name' => 'Super Admin', 'slug' => 'super_admin'],
                ['name' => 'Administrator', 'slug' => 'administrator'],
                ['name' => 'Dokter', 'slug' => 'dokter'],
                ['name' => 'Petugas Pendaftaran', 'slug' => 'petugas_pendaftaran'],
                ['name' => 'Apoteker', 'slug' => 'apoteker'],
            ];

            foreach ($roles as $roleData) {
                Role::updateOrCreate(
                    ['slug' => $roleData['slug']],
                    ['name' => $roleData['name']]
                );
            }

            // Fetch Role Instance untuk kemudahan attach
            $superAdminRole = Role::where('slug', 'super_admin')->first();
            $dokterRole     = Role::where('slug', 'dokter')->first();

            // ----------------------------------------------------
            // 2. SEED TABEL: users & role_user
            // ----------------------------------------------------
            
            // Admin 1
            $admin1 = User::updateOrCreate(
                ['username' => 'admin1'],
                [
                    'name'           => 'Administrator Klinik 1',
                    'email'          => 'admin1@klinik.com',
                    'phone_number'   => '6282257481032',
                    'phone_verified' => true,
                    'phone_verified_at' => now(),
                    'password'       => Hash::make('password123'),
                    'sdmk_id'        => null,
                    'two_factor_enabled' => true,
                ]
            );
            $admin1->roles()->syncWithoutDetaching([$superAdminRole->id]);

            // Admin 2
            $admin2 = User::updateOrCreate(
                ['username' => 'admin2'],
                [
                    'name'           => 'Administrator Klinik 2',
                    'email'          => 'admin2@klinik.com',
                    'phone_number'   => '6281511210060',
                    'phone_verified' => true,
                    'phone_verified_at' => now(),
                    'password'       => Hash::make('password123'),
                    'sdmk_id'        => null,
                    'two_factor_enabled' => true,
                ]
            );
            $admin2->roles()->syncWithoutDetaching([$superAdminRole->id]);

            // Dokter Budi
            $dokter = User::updateOrCreate(
                ['username' => 'dokter_budi'],
                [
                    'name'           => 'dr. Budi Santoso',
                    'email'          => 'budi@klinik.com',
                    'phone_number'   => '6281511210062',
                    'phone_verified' => true,
                    'phone_verified_at' => now(),
                    'password'       => Hash::make('password123'),
                    'sdmk_id'        => 1001, // Dummy ID SDMK dari Kemenkes
                    'two_factor_enabled' => false,
                ]
            );
            $dokter->roles()->syncWithoutDetaching([$dokterRole->id]);

            // ----------------------------------------------------
            // 3. SEED TABEL: password_reset_tokens
            // ----------------------------------------------------
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => 'budi@klinik.com'],
                [
                    'token'      => Hash::make(Str::random(40)),
                    'created_at' => now(),
                ]
            );

            // ----------------------------------------------------
            // 4. SEED TABEL: refresh_tokens
            // ----------------------------------------------------
            DB::table('refresh_tokens')->updateOrInsert(
                ['token' => 'dummy_refresh_token_sample_12345'],
                [
                    'user_id'      => $admin1->id,
                    'device_id'    => 'DEV-WEB-ADMIN1',
                    'ip_address'   => '127.0.0.1',
                    'user_agent'   => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    'last_used_at' => now(),
                    'expires_at'   => now()->addDays(30),
                    'is_revoked'   => false,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]
            );

            // ----------------------------------------------------
            // 5. SEED TABEL: user_sessions
            // ----------------------------------------------------
            DB::table('user_sessions')->updateOrInsert(
                ['id' => Str::uuid()->toString()],
                [
                    'user_id'          => $admin1->id,
                    'device_id'        => 'DEV-WEB-ADMIN1',
                    'ip_address'       => '127.0.0.1',
                    'user_agent'       => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    'is_active'        => true,
                    'last_activity_at' => now(),
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]
            );

            // ----------------------------------------------------
            // 6. SEED TABEL: trusted_devices
            // ----------------------------------------------------
            DB::table('trusted_devices')->updateOrInsert(
                [
                    'user_id'   => $admin1->id,
                    'device_id' => 'DEV-WEB-ADMIN1',
                ],
                [
                    'device_name'    => 'Chrome on Windows 10',
                    'ip_address'     => '127.0.0.1',
                    'user_agent'     => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    'is_trusted'     => true,
                    'last_active_at' => now(),
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]
            );

            // ----------------------------------------------------
            // 7. SEED TABEL: audit_logs
            // ----------------------------------------------------
            DB::table('audit_logs')->insert([
                [
                    'user_id'     => $admin1->id,
                    'action'      => 'SYSTEM_INIT',
                    'description' => 'Inisialisasi seeder sistem awal.',
                    'ip_address'  => '127.0.0.1',
                    'user_agent'  => 'Console/Seeder',
                    'payload'     => json_encode(['status' => 'success']),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ],
                [
                    'user_id'     => $dokter->id,
                    'action'      => 'LOGIN',
                    'description' => 'User dr. Budi Santoso berhasil masuk ke sistem.',
                    'ip_address'  => '192.168.1.50',
                    'user_agent'  => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                    'payload'     => json_encode(['login_type' => 'password']),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ],
            ]);
        });
    }
}