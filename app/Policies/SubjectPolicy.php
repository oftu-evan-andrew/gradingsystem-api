<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Subject;

class SubjectPolicy
{
    public function finalize(User $user, Subject $subject): bool
    {
        return $user->role === 'admin';
    }
}
