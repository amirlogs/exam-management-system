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
        $normalizedType = strtoupper($type);
        if (! array_key_exists($normalizedType, self::$map)) {
            throw new InvalidArgumentException("Invalid question type: {$type}");
        }

        return new self::$map[$normalizedType];
    }
}
