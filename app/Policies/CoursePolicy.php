<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Course;

class CoursePolicy
{
    public function finalize(User $user, Course $course): bool
    {
        return $user->role === 'admin';
    }
}
