<?php

namespace App\Services;

use App\Commit\InstructorCommitter;
use App\Commit\QuestionCommitter;
use App\Commit\SectionCommitter;
use App\Commit\StudentCommitter;

class ImportCommitterFactory
{
    public static function create(string $type): string
    {
        return match ($type) {
            'students' => StudentCommitter::class,
            'instructors' => InstructorCommitter::class,
            'sections' => SectionCommitter::class,
            'questions' => QuestionCommitter::class, 
            // 'user' => UserCommitter::class,
        };
    }
}
