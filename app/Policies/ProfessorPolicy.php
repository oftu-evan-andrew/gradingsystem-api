<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Professor;

class ProfessorPolicy
{
    public function finalize(User $user, Professor $professor): bool
    {
        return $user->role === 'admin';
    }
}
