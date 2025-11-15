<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->withoutTwoFactor()->create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
        ]);

        User::factory()->withoutTwoFactor()->create([
            'name' => 'Teacher',
            'email' => 'teacher@example.com',
            'email_verified_at' => now(),
        ]);

        User::factory()->withoutTwoFactor()->create([
            'name' => 'Student',
            'email' => 'student@example.com',
            'email_verified_at' => now(),
        ]);

        foreach (range(1, 20) as $count) {
            User::factory()->withoutTwoFactor()->create([
                'name' => "user{$count}",
                'email' => "user{$count}@example.com",
                'email_verified_at' => now(),
            ]);
        }
    }
}
