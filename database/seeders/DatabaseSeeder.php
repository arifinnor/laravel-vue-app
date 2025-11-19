<?php

namespace Database\Seeders;

use Database\Seeders\Finance\ChartOfAccountSeeder;
use Database\Seeders\Finance\TransactionTypeSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(TeacherSeeder::class);
        $this->call(StudentSeeder::class);
        $this->call(ChartOfAccountSeeder::class);
        $this->call(TransactionTypeSeeder::class);
    }
}
