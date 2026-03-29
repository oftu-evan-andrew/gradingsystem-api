<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Section;

class SectionPolicy
{
    public function viewAny(User $user): bool { 
        return in_array($user->role, ['admin', 'professor']);
    }

    public function view(User $user, Section $section): bool { 
        return in_array($user->role, ['admin', 'professor']);
    }

    public function create(User $user): bool { 
        return $user->role === 'admin';
    }

    public function update(User $user, Section $section): bool { 
        return $user->role === 'admin';
    }

    public function delete(User $user, Section $section): bool { 
        return $user->role === 'admin';
    }
}
