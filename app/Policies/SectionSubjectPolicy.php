<?php

namespace App\Policies; 

use App\Models\User;
use App\Models\SectionSubject;

class SectionSubjectPolicy 
{
    public function viewAny(User $user): bool { 
        return in_array($user->role, ['admin', 'professor']);
    }

    public function view(User $user, SectionSubject $sectionSubject): bool { 
        return in_array($user->role, ['admin', 'professor']);
    }

    public function create(User $user): bool { 
        return $user->role === 'admin';
    }

    public function update(User $user, SectionSubject $sectionSubject): bool { 
        return $user->role === 'admin';
    }

    public function delete(User $user, SectionSubject $sectionSubject): bool { 
        return $user->role === 'admin';
    }
}