<?php

namespace Database\Seeders;

use App\Models\Teacher;
use Illuminate\Database\Seeder;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Teacher::factory()->create([
            'teacher_number' => 'TCH-00001',
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1234567890',
        ]);

        Teacher::factory()->create([
            'teacher_number' => 'TCH-00002',
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'phone' => '+1234567891',
        ]);

        Teacher::factory()->create([
            'teacher_number' => 'TCH-00003',
            'name' => 'Robert Johnson',
            'email' => 'robert.johnson@example.com',
        ]);

        foreach (range(1, 20) as $count) {
            Teacher::factory()->create();
        }
    }
}
