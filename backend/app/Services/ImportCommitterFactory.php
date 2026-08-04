<?php

namespace App\Services;

use App\Commit\InstructorCommitter;
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
            // 'user' => UserCommitter::class,
        };
    }
}
