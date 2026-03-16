<?php

namespace App\Policies; 

use App\Models\User;
use App\Models\PeriodicGrade;

class PeriodicGradePolicy 
{
    public function view(User $user, PeriodicGrade $periodicGrade): bool {
        if ($user->role === 'student') {
            return $periodicGrade->status === 'finalized';
        }
        return true;
    }

    public function finalize(User $user, PeriodicGrade $periodicGrade): bool {
        if ($user->role === 'admin') {
            return true;
        }

        $professor = $user->professor; 
        if (!$professor) {
            return false; 
        }

        return $periodicGrade->classStanding->sectionSubject->professor_id === $professor->professor_id;
    }
}