<?php

namespace App\Policies;

use App\Models\ExamAttempt;
use App\Models\User;

class ExamAttemptPolicy
{
    public function canSubmitAnswer(User $user, ExamAttempt $examAttempt)
    {
       return $examAttempt->student_id === $user->student->id;
    }
}
