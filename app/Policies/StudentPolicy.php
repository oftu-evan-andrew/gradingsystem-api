<?php

namespace App\Policies; 

use App\Models\User;
use App\Models\Student;

class StudentPolicy 
{
    public function viewAny(User $user): bool { 
        return in_array($user->role, ['admin', 'professor']);
    }

    public function view(User $user, Student $student): bool { 
        return in_array($user->role, ['admin', 'professor']);
    }

    public function create(User $user): bool { 
        return $user->role === 'admin';
    }

    public function update(User $user, Student $student): bool { 
        return $user->role === 'admin';
    }

    public function delete(User $user, Student $student): bool { 
        return $user->role === 'admin';
    }
}