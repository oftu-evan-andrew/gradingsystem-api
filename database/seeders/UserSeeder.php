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

        $sections = Section::all();
        foreach ($sections as $section) {
            for ($i = 1; $i <= 5; $i++) {
                $email = strtolower("student{$i}.{$section->section_name}@example.com");
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'first_name' => "Student{$i}",
                        'last_name' => $section->section_name . "-{$i}",
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
            }
        }

        $this->command->info('Admin user created/verified: admin@example.com / password123');
        $this->command->info('Students created/verified for all sections');
    }
}
