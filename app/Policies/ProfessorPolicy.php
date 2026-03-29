<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Professor;

class ProfessorPolicy
{
    public function viewAny(User $user): bool { 
        return in_array($user->role, ['admin', 'student', 'professor']);
    }

    public function view(User $user, Professor $professor): bool { 
        return in_array($user->role, ['admin', 'student', 'professor']);
    }

    public function create(User $user): bool { 
        return $user->role === 'admin';
    }

    public function update(User $user, Professor $professor): bool { 
        return $user->role === 'admin';
    }

    public function delete(User $user, Professor $professor): bool { 
        return $user->role === 'admin';
    }
}
