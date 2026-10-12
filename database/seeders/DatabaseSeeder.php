<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        
        if (app()->environment(['local', 'testing'])) {
            $this->call(QueueSystemSeeder::class);
            $this->call(UserSeeder::class);
            $this->call(RegistrationMasterSeeder::class);
            $this->call(SdmkMasterSeeder::class);
        }
    }
}
