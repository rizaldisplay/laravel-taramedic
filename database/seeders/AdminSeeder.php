<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Akun Super Admin Utama 1
            User::updateOrCreate(
                ['username' => 'admin1'],
                [
                    'name'         => 'Administrator Klinik 1',
                    'email'        => 'admin1@klinik.com',
                    'phone_number' => '6282257481032',
                    'password'     => Hash::make('password123'),
                    'role'         => 'super_admin',
                ]
            );

            // 2. Akun Super Admin Utama 2
            User::updateOrCreate(
                ['username' => 'admin2'],
                [
                    'name'         => 'Administrator Klinik 2',
                    'email'        => 'admin2@klinik.com',
                    'phone_number' => '6281511210060',
                    'password'     => Hash::make('password123'),
                    'role'         => 'super_admin',
                ]
            );

            // 3. Akun Dokter (Gunakan role yang diizinkan database)
            User::updateOrCreate(
                ['username' => 'dokter_budi'],
                [
                    'name'         => 'dr. Budi Santoso',
                    'email'        => 'budi@klinik.com',
                    'phone_number' => '6281511210062',
                    'password'     => Hash::make('password123'),
                    'role'         => 'admin', // Diubah dari 'dokter' ke 'admin'
                ]
            );
        });
    }
}