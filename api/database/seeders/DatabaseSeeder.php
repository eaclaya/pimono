<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Transaction;
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
        $now = now();
        $user = User::firstOrCreate([
                'email' => 'admin@example.com',
            ],
            [
            'name' => 'Main User',
            'balance' => 10000,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        foreach (range(1, 100) as $i) {
            $transactions = [];
            for ($j = 1; $j <= 1000; $j++) {
                $transactions[] = [
                    'sender_id' => $i,
                    'receiver_id' => $j,
                    'amount' => fake()->randomFloat(4, 1, 100),
                    'commission_fee' => fake()->randomFloat(4, 0, 1),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            Transaction::insert($transactions);
        }

    }
}
