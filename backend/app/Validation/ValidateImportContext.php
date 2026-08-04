<?php

namespace App\Validation;

use Illuminate\Support\Facades\Validator;

class ValidateImportContext
{
    public static function validate(string $type, ?array $context): array
    {
        return match ($type) {
            'students', 'sections' => Validator::make($context ?? [], [
                'semester_id' => 'required|integer|exists:semesters,id',
                'year_level' => 'required|integer|min:1',
            ])->errors()->all(),

            // 'user' => Validator::make($context ?? [], [
            //     'role_name' => 'required|string|exists:roles,name',
            //     'university_id' => 'nullable|integer|exists:universities,id',
            //     'college_id' => 'nullable|integer|exists:colleges,id',
            //     'department_id' => 'nullable|integer|exists:departments,id',
            // ])->errors()->all(),
            //
            'instructor' => [],
            default => ['Unknown import type.'],
        };
    }
}
