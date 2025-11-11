<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate([
                'email' => 'admin@example.com',
            ],
            [
            'name' => 'Main User',
            'balance' => 1000,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        User::factory(10)->create();
    }
}
