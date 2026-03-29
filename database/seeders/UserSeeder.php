<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use App\Models\Section;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        $section = Section::first();
        if ($section) {
            $user = User::firstOrCreate(
                ['email' => 'student@example.com'],
                [
                    'first_name' => 'Jane',
                    'last_name' => 'Doe',
                    'password' => Hash::make('password123'),
                    'role' => 'student',
                ]
            );

            Student::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'section_id' => $section->section_id,
                    'is_irregular' => false,
                ]
            );

            $this->command->info('Student created/verified: student@example.com / password123');
        }

        $this->command->info('Admin user created/verified: admin@example.com / password123');
    }
}
