<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Section;
use App\Models\Subject;
use App\Models\SectionSubject;
use App\Models\User;
use App\Models\Professor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CurriculumSeeder extends Seeder
{
    public function run(): void
    {
        $this->createProfessors();
        $this->createCourses();
        $this->createSubjects();
        $this->createSectionsAndSectionSubjects();
    }

    private function createProfessors(): void
    {
        $professors = [
            ['first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'email' => 'juan.delacruz@example.com'],
            ['first_name' => 'Maria', 'last_name' => 'Santos', 'email' => 'maria.santos@example.com'],
            ['first_name' => 'Pedro', 'last_name' => 'Penduko', 'email' => 'pedro.penduko@example.com'],
            ['first_name' => 'Ana', 'last_name' => 'Reyes', 'email' => 'ana.reyes@example.com'],
            ['first_name' => 'Jose', 'last_name' => 'Rizal', 'email' => 'jose.rizal@example.com'],
        ];

        foreach ($professors as $prof) {
            $user = User::firstOrCreate(
                ['email' => $prof['email']],
                [
                    'first_name' => $prof['first_name'],
                    'last_name' => $prof['last_name'],
                    'password' => Hash::make('password123'),
                    'role' => 'professor',
                ]
            );

            Professor::firstOrCreate(['user_id' => $user->id]);
        }

        $this->command->info('Professors created/verified: ' . count($professors));
    }

    private function createCourses(): void
    {
        $courses = [
            ['course_name' => 'Computer Science'],
            ['course_name' => 'Entrepreneurship'],
            ['course_name' => 'Computer Engineering'],
        ];

        foreach ($courses as $course) {
            Course::firstOrCreate(['course_name' => $course['course_name']]);
        }
    }

    private function createSubjects(): void
    {
        $subjects = [
            ['subject_code' => 'CC123', 'subject_name' => 'Intermediate Computer Programming 2', 'units' => 3],
            ['subject_code' => 'CS121', 'subject_name' => 'Discrete Structures 1', 'units' => 3],
            ['subject_code' => 'CC225', 'subject_name' => 'Information Management', 'units' => 3],
            ['subject_code' => 'CS224', 'subject_name' => 'Algorithms and Complexity', 'units' => 3],
            ['subject_code' => 'MAT222', 'subject_name' => 'Differential Calculus', 'units' => 3],
            ['subject_code' => 'CIS222', 'subject_name' => 'CISCO 2 - Routing Protocols and Concepts', 'units' => 3],
            ['subject_code' => 'CS328', 'subject_name' => 'Programming Languages', 'units' => 3],
            ['subject_code' => 'CS329', 'subject_name' => 'Software Engineering 1', 'units' => 3],
            ['subject_code' => 'CS330', 'subject_name' => 'Social Issues and Professional Practice', 'units' => 3],
            ['subject_code' => 'CSE322', 'subject_name' => 'CS Elective 2', 'units' => 2],
            ['subject_code' => 'ACT321', 'subject_name' => 'Fundamentals of Accounting', 'units' => 3],
            ['subject_code' => 'CIS324', 'subject_name' => 'CISCO 4 - Accessing WAN', 'units' => 3],
            ['subject_code' => 'CS4216', 'subject_name' => 'CS Thesis Writing 2', 'units' => 3],
            ['subject_code' => 'CS4217', 'subject_name' => 'Network and Communications', 'units' => 1],
            ['subject_code' => 'RIZ411', 'subject_name' => 'Life and Works of Rizal', 'units' => 3],
            ['subject_code' => 'TNP421', 'subject_name' => 'Technopreneurship', 'units' => 3],
            ['subject_code' => 'GE4', 'subject_name' => 'Mathematics in the Modern World', 'units' => 3],
            ['subject_code' => 'GE5', 'subject_name' => 'Purposive Communication', 'units' => 3],
            ['subject_code' => 'GE6', 'subject_name' => 'Art Appreciation', 'units' => 3],
            ['subject_code' => 'GE9', 'subject_name' => 'Life and Works of Rizal', 'units' => 3],
            ['subject_code' => 'GE10', 'subject_name' => 'Living in the IT-Era', 'units' => 3],
            ['subject_code' => 'GE8', 'subject_name' => 'Ethics', 'units' => 3],
            ['subject_code' => 'PATHFIT2', 'subject_name' => 'Exercise-Based Fitness Activities', 'units' => 2],
            ['subject_code' => 'PATHFIT4', 'subject_name' => 'Martial Arts', 'units' => 2],
            ['subject_code' => 'NSTP2', 'subject_name' => 'Literacy Training Service 2', 'units' => 3],
            ['subject_code' => 'ENT2', 'subject_name' => 'Microeconomics', 'units' => 3],
            ['subject_code' => 'ENT5', 'subject_name' => 'Social Entrepreneurship', 'units' => 3],
            ['subject_code' => 'ENT6', 'subject_name' => 'Innovation Management', 'units' => 3],
            ['subject_code' => 'ENT7', 'subject_name' => 'Pricing and Costing', 'units' => 3],
            ['subject_code' => 'HRM', 'subject_name' => 'Human Resource Management', 'units' => 3],
            ['subject_code' => 'ACTG2', 'subject_name' => 'Accounting, Business and Management 2', 'units' => 3],
            ['subject_code' => 'ENT9', 'subject_name' => 'Business Plan Preparation', 'units' => 3],
            ['subject_code' => 'SMGT', 'subject_name' => 'Strategic Management', 'units' => 3],
            ['subject_code' => 'MOE2', 'subject_name' => 'Microsoft Productivity Tool 2', 'units' => 3],
            ['subject_code' => 'ELT3', 'subject_name' => 'Elective 3', 'units' => 3],
            ['subject_code' => 'ELT4', 'subject_name' => 'Elective 4', 'units' => 3],
            ['subject_code' => 'ENT11', 'subject_name' => 'Business Plan Implementation', 'units' => 5],
            ['subject_code' => 'ENT12', 'subject_name' => 'International Business and Trade', 'units' => 3],
            ['subject_code' => 'ENT13', 'subject_name' => 'Programs and Policies on Enterprise Development', 'units' => 5],
            ['subject_code' => 'MDA', 'subject_name' => 'Multimedia Development and Application', 'units' => 5],
            ['subject_code' => 'MAT122', 'subject_name' => 'Calculus 2', 'units' => 3],
            ['subject_code' => 'NPS122', 'subject_name' => 'Physics for Engineers', 'units' => 6],
            ['subject_code' => 'MAT123', 'subject_name' => 'Engineering Data Analysis', 'units' => 3],
            ['subject_code' => 'CPE124', 'subject_name' => 'Discrete Mathematics', 'units' => 3],
            ['subject_code' => 'A222', 'subject_name' => 'Fundamentals of Electronic Circuits', 'units' => 3],
            ['subject_code' => 'BES212', 'subject_name' => 'Computer-Aided Drafting', 'units' => 3],
            ['subject_code' => 'BES413', 'subject_name' => 'Engineering Management', 'units' => 3],
            ['subject_code' => 'CPE215', 'subject_name' => 'Data Structure and Algorithm', 'units' => 3],
            ['subject_code' => 'CPE318', 'subject_name' => 'Numerical Methods', 'units' => 4],
            ['subject_code' => 'COG ELE1', 'subject_name' => 'Microsoft Software Development', 'units' => 3],
            ['subject_code' => 'CPE3213', 'subject_name' => 'Basic Occupational Health and Safety', 'units' => 3],
            ['subject_code' => 'CPE3214', 'subject_name' => 'Introduction to HDL', 'units' => 3],
            ['subject_code' => 'CPE3215', 'subject_name' => 'Computer Networks and Security', 'units' => 3],
            ['subject_code' => 'CPE3216', 'subject_name' => 'Microprocessors', 'units' => 6],
            ['subject_code' => 'CPE3217', 'subject_name' => 'Methods of Research', 'units' => 2],
            ['subject_code' => 'PPC', 'subject_name' => 'Philippine Popular Culture', 'units' => 3],
            ['subject_code' => 'TE2', 'subject_name' => 'CISCO 2 - Routing Protocols and Concepts', 'units' => 3],
            ['subject_code' => 'TE4', 'subject_name' => 'CISCO 4 - Accessing the WAN', 'units' => 5],
            ['subject_code' => 'CPE4224', 'subject_name' => 'CpE Practice and Design 2', 'units' => 6],
            ['subject_code' => 'CPE4225', 'subject_name' => 'Seminars and Field Trips', 'units' => 3],
            ['subject_code' => 'CPE4226', 'subject_name' => 'CpE Laws and Professional Practice', 'units' => 2],
            ['subject_code' => 'TP1', 'subject_name' => 'Technopreneurship', 'units' => 3],
        ];

        foreach ($subjects as $subject) {
            Subject::firstOrCreate(['subject_code' => $subject['subject_code']], $subject);
        }
    }

    private function createSectionsAndSectionSubjects(): void
    {
        $schoolYear = '2025-2026';
        $semester = 2;

        $sectionConfigs = [
            ['BS2MA', 1, 'Computer Science', ['CC123', 'CS121', 'GE4', 'GE5', 'GE6', 'PATHFIT2', 'NSTP2']],
            ['BS2AA', 1, 'Computer Science', ['CC123', 'CS121', 'GE4', 'GE5', 'GE6', 'PATHFIT2', 'NSTP2']],
            ['BS2EA', 1, 'Computer Science', ['CC123', 'CS121', 'GE4', 'GE5', 'GE6', 'PATHFIT2', 'NSTP2']],
            ['BS4MA', 2, 'Computer Science', ['CC225', 'CS224', 'MAT222', 'GE9', 'GE10', 'CIS222', 'PATHFIT4']],
            ['BS4EA', 2, 'Computer Science', ['CC225', 'CS224', 'MAT222', 'GE9', 'GE10', 'CIS222', 'PATHFIT4']],
            ['BS6MA', 3, 'Computer Science', ['CS328', 'CS329', 'CS330', 'CSE322', 'ACT321', 'CIS324']],
            ['BS6AA', 3, 'Computer Science', ['CS328', 'CS329', 'CS330', 'CSE322', 'ACT321']],
            ['BS8EA', 4, 'Computer Science', ['CS4216', 'CS4217', 'RIZ411', 'TNP421']],
            ['BN2AA', 1, 'Entrepreneurship', ['GE4', 'GE5', 'GE6', 'ENT2', 'PATHFIT2', 'NSTP2']],
            ['BN2AB', 1, 'Entrepreneurship', ['GE4', 'GE5', 'GE6', 'ENT2', 'PATHFIT2', 'NSTP2']],
            ['BN4MA', 2, 'Entrepreneurship', ['GE10', 'ENT5', 'ENT6', 'ENT7', 'HRM', 'PATHFIT4', 'ACTG2']],
            ['BN6AA', 3, 'Entrepreneurship', ['ENT9', 'SMGT', 'MOE2', 'ELT3', 'ELT4']],
            ['BN8EA', 4, 'Entrepreneurship', ['ENT11', 'ENT12', 'ENT13', 'MDA']],
            ['BG2AA', 1, 'Computer Engineering', ['GE4', 'GE5', 'GE6', 'MAT122', 'NPS122', 'MAT123', 'CPE124', 'PATHFIT2', 'NSTP2']],
            ['BG4MA', 2, 'Computer Engineering', ['A222', 'BES212', 'BES413', 'CPE215', 'CPE318', 'COG ELE1', 'GE10', 'PATHFIT4']],
            ['BG6MA', 3, 'Computer Engineering', ['CPE3213', 'CPE3214', 'CPE3215', 'CPE3216', 'CPE3217', 'PPC', 'TE2', 'GE8']],
            ['BG8EA', 4, 'Computer Engineering', ['TE4', 'CPE4224', 'CPE4225', 'CPE4226', 'TP1']],
        ];

        $professors = Professor::all();
        $professorIndex = 0;

        foreach ($sectionConfigs as $config) {
            [$sectionName, $yearLevel, $courseName, $subjectCodes] = $config;
            $course = Course::where('course_name', 'like', "%$courseName%")->first();

            $section = Section::firstOrCreate(
                ['section_name' => $sectionName, 'school_year' => $schoolYear],
                [
                    'year_level' => $yearLevel,
                    'course_id' => $course->id,
                ]
            );

            foreach ($subjectCodes as $subjectCode) {
                $subject = Subject::where('subject_code', $subjectCode)->first();
                if ($subject) {
                    $professor = $professors[$professorIndex % count($professors)];
                    
                    SectionSubject::firstOrCreate(
                        [
                            'section_id' => $section->section_id,
                            'subject_id' => $subject->id,
                            'semester' => $semester,
                        ],
                        ['professor_id' => $professor->professor_id]
                    );
                }
            }
            $professorIndex++;
        }

        $this->command->info('Sections and section-subjects created/verified');
    }
}
