<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SectionSubject;

class SectionSubjectPolicy
{
    public function finalize(User $user, SectionSubject $sectionSubject): bool
    {
        return in_array($user->role, ['admin', 'professor']);
    }
}
