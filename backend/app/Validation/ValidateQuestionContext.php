<?php

namespace App\Validation;

use Illuminate\Support\Facades\Validator;

class ValidateQuestionContext
{
    public static function validate(?array $context): array
    {
        if (empty($context)) {
            return [];
        }

        return Validator::make($context, [
            'course_id' => ['sometimes', 'nullable', 'integer', 'exists:courses,id'],
            'exam_id' => ['sometimes', 'nullable', 'integer', 'exists:exams,id'],
        ])->errors()->all();
    }
}
