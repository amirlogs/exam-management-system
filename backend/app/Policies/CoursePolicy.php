<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function create(User $user, Course $course): bool
    {
        logger('this is the first line of the create method');
        if ($user->hasPermission('question.create_all')) {
            return true;
        }

        $instructor = $user->instructor;

        if (! $instructor) {
            return false;
        }

        return $instructor->courseOfferings()->where('course_id', $course->id) ->exists();
    }
}
