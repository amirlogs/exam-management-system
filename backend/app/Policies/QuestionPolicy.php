<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    public function view(User $user, Question $question): bool
    {
        if ($user->hasPermission('question.view_all')) {
            return true;
        }

        $instructor = $user->instructor;

        if (! $instructor) {
            return false;
        }

        return $instructor->courseOfferings()
            ->where('course_id', $question->course_id) ->exists();
    }


    public function update(User $user, Question $question): bool
    {
        if ($user->hasPermission('question.update_all')) {
            return true;
        }

        $instructor = $user->instructor;

        if (! $instructor) {
            return false;
        }

        return $instructor->courseOfferings()
            ->where('course_id', $question->course_id) ->exists();
    }

    public function archive(User $user, Question $question): bool
    {
        if ($user->hasPermission('question.archive_all')) {
            return true;
        }

        $instructor = $user->instructor;

        if (! $instructor) {
            return false;
        }

        return $instructor->courseOfferings() ->where('course_id', $question->course_id) ->exists();
    }

    public function restore(User $user, Question $question): bool
    {
        if ($user->hasPermission('question.restore_all')) {
            return true;
        }

        $instructor = $user->instructor;

        if (! $instructor) {
            return false;
        }

        return $instructor->courseOfferings() ->where('course_id', $question->course_id) ->exists();
    }
}
