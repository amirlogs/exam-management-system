<?php

namespace App\Services;

use App\Validation\InstructorValidator;
use App\Validation\SectionValidator;
use App\Validation\StudentValidator;
use App\Validation\UserValidator;
use InvalidArgumentException;

class ImportValidatorFactory
{
    protected static array $map = [
        'students' => StudentValidator::class,
        'instructors' => InstructorValidator::class,
        'sections' => SectionValidator::class,
        'users' => UserValidator::class,
    ];

    public static function create(string $type)
    {
        if (! array_key_exists($type, self::$map)) {
            throw new InvalidArgumentException("Invalid import type: {$type}");
        }

        return new self::$map[$type];
    }
}
