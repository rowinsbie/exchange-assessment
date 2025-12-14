<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Asset;
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
        $users = [
            [
                'name' => 'Test User 1',
                'email' => 'test1@seikirowinsbie.com',
                'password' => bcrypt('pass123'),
                'balance' => 10000, 
            ],
            [
                'name' => 'Test User 1',
                'email' => 'test2@seikirowinsbie.com',
                'password' => bcrypt('pass123'),
                'balance' => 0, 
            ]
        ];

        foreach($users as $user) {
            $user = User::create($user);

            Asset::create([
                'user_id' => $user->id,
                'symbol' => 'BTC',
                'amount' => 100000,
                'locked_amount' => 0,
            ]);

            Asset::create([
                'user_id' => $user->id,
                'symbol' => 'ETH',
                'amount' => 10,
                'locked_amount' => 0,
            ]);
        }
    }
}
