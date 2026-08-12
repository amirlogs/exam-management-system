<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;

class ExamPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct() {}

    public function viewAsStudent(User $user, Exam $exam)
    {
        $student = $user->student;
        if (! $student) {
            return false;
        }

        return $student->enrollments()
            ->where('course_offering_id', $exam->course_offering_id)
            ->where('status', 'active')
            ->exists();
    }

}
