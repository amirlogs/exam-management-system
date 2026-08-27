<?php

namespace App\Services;

use App\Commit\InstructorCommitter;
use App\Commit\QuestionCommitter;
use App\Commit\SectionCommitter;
use App\Commit\StudentCommitter;
use App\Commit\UserCommitter;
use InvalidArgumentException;

class ImportCommitterFactory
{
    protected static array $map = [
        'students' => StudentCommitter::class,
        'instructors' => InstructorCommitter::class,
        'sections' => SectionCommitter::class,
        'questions' => QuestionCommitter::class,
        'users' => UserCommitter::class,
    ];

    public static function create(string $type)
    {
        if (! array_key_exists($type, self::$map)) {
            throw new InvalidArgumentException("Invalid import type: {$type}");
        }

        return new self::$map[$type];
    }
}
