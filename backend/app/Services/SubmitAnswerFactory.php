<?php

namespace App\Services;

use App\Validation\SubmitNonOptionAnswerValidator;
use App\Validation\SubmitOptionAnswerValidator;
use InvalidArgumentException;

class SubmitAnswerFactory
{
    protected static array $map = [
        'MCQ' => SubmitOptionAnswerValidator::class,
        'TRUE_FALSE' => SubmitOptionAnswerValidator::class,
        'SHORT_ANSWER' => SubmitNonOptionAnswerValidator::class,
        'ESSAY' => SubmitNonOptionAnswerValidator::class,
    ];

    public static function create(string $type)
    {
        if (! array_key_exists($type, self::$map)) {
            throw new InvalidArgumentException("Invalid import type: {$type}");
        }
        
        return new self::$map[$type];
    }
}
