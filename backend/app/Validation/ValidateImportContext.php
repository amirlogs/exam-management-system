<?php

namespace App\Validation;

use Illuminate\Support\Facades\Validator;

class ValidateImportContext
{
    public static function validate(string $type, ?array $context): array
    {
        return match ($type) {
            'students' => Validator::make($context ?? [], [
                'semester_id' => 'required|integer|exists:semesters,id',
                'year_level' => 'required|integer|min:1',
            ])->errors()->all(),

            'questions' => Validator::make($context ?? [], [
                'course_id' => 'required|integer|exists:courses,id',
                'exam_id' => 'sometimes|integer|exists:exams,id',
            ])->errors()->all(),

            'sections' => Validator::make($context ?? [], [
                'semester_id' => 'required|integer|exists:semesters,id',
            ])->errors()->all(),

            'instructors',
            'users' => [],

            default => ['Unknown import type.'],
        };
    }
}
