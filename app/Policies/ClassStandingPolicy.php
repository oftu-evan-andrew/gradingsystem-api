<?php

namespace App\Policies; 

use App\Models\User;
use App\Models\ClassStanding;

class ClassStandingPolicy 
{
    public function view(User $user, ClassStanding $classStanding): bool {
        if ($user->role === 'student') {
            return $classStanding->status === 'finalized';
        }
        return true;
    }

    public function finalize(User $user, ClassStanding $classStanding): bool {
        if ($user->role === 'admin') {
            return true;
        }

        $professor = $user->professor; 
        if (!$professor) {
            return false; 
        }

        return $classStanding->sectionSubject->professor_id === $professor->professor_id;
    }
}