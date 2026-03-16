<?php

namespace App\Policies; 

use App\Models\User;
use App\Models\StudentFinalGrade;

class StudentFinalGradePolicy 
{
    public function view(User $user, StudentFinalGrade $studentFinalGrade): bool {
        if ($user->role === 'student') {
            return $studentFinalGrade->status === 'finalized';
        }
        return true;
    }

    public function finalize(User $user, StudentFinalGrade $studentFinalGrade): bool {
        if ($user->role === 'admin') {
            return true;
        }

        $professor = $user->professor; 
        if (!$professor) {
            return false; 
        }

        return $studentFinalGrade->sectionSubject->professor_id === $professor->professor_id;
    }
}