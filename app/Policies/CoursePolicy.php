<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Course;

class CoursePolicy
{
    public function viewAny(User $user): bool { 
        return in_array($user->role, ['admin', 'professor']);
    }

    public function view(User $user, Course $course): bool { 
        return in_array($user->role, ['admin', 'professor']);
    }

    public function create(User $user): bool { 
        return $user->role === 'admin';
    }

    public function update(User $user, Course $course): bool { 
        return $user->role === 'admin';
    }

    public function delete(User $user, Course $course): bool { 
        return $user->role === 'admin';
    }
}
