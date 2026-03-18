<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Section;

class SectionPolicy
{
    public function finalize(User $user, Section $section): bool
    {
        return in_array($user->role, ['admin', 'professor']);
    }
}
