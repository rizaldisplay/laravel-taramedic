<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $password = app()->environment('testing')
            ? 'password'
            : env('QUEUE_DEMO_PASSWORD');

        if (! is_string($password) || $password === '') {
            $password = Str::password(24);
            $this->command?->warn('QUEUE_DEMO_PASSWORD is not set; demo users were seeded with a generated password. Reset them from Admin > Pengguna if needed.');
        }

        $users = [
            [
                'name' => 'Administrator',
                'email' => 'admin@antrian.test',
                'password' => Hash::make($password),
                'role' => UserRole::Admin,
            ],
            [
                'name' => 'Siti Rahayu',
                'email' => 'operator1@antrian.test',
                'password' => Hash::make($password),
                'role' => UserRole::Operator,
            ],
            [
                'name' => 'Budi Santoso',
                'email' => 'operator2@antrian.test',
                'password' => Hash::make($password),
                'role' => UserRole::Operator,
            ],
            [
                'name' => 'Dewi Lestari',
                'email' => 'operator3@antrian.test',
                'password' => Hash::make($password),
                'role' => UserRole::Operator,
            ],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                $user,
            );
        }
    }
}
