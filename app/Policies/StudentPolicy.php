<?php

namespace App\Policies; 

use App\Models\User;
use App\Models\Student;

class StudentPolicy 
{
    public function view(User $user, Student $student): bool {
        if ($user->role === 'student') {
            return $student->status === 'finalized';
        }
        return true;
    }

    public function finalize(User $user, Student $student): bool {
        if ($user->role === 'admin') {
            return true;
        }

        $professor = $user->professor; 
        if (!$professor) {
            return false; 
        }

        return $student->sectionSubject->professor_id === $professor->professor_id;
    }
}