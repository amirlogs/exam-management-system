<?php

namespace App\Validation;

use App\Models\Program;
use App\Models\Section;
use Illuminate\Support\Facades\Validator;

class SectionValidator
{
    private static array $rules = [
        'program_code' => ['required', 'string', 'exists:programs,code'],
        'year_level' => ['required', 'integer', 'min:1'],
        'name' => ['required', 'string', 'min:1', 'max:255'],
    ];

    private static array $updateRules = [
        'program_code' => ['sometimes', 'string', 'exists:programs,code'],
        'year_level' => ['sometimes', 'integer', 'min:1'],
        'name' => ['sometimes', 'string', 'min:1', 'max:255'],
    ];

    public static function validate(array $data, array $context = []): array
    {
        $validator = Validator::make($data, self::$rules);

        $errors = $validator->errors()->all();
        if (! empty($errors)) {
            return $errors;
        }
        $program = Program::where('code', $data['program_code'])->first();
        $semesterId = $context['semester_id'] ?? null;

        if (! $semesterId) {
            $errors[] = 'No semester_id was provided for this import batch.';

            return $errors;
        }

        if ((int) $data['year_level'] > $program->duration_years) {
            $errors[] = "Year level {$data['year_level']} exceeds {$program->code}'s program length of {$program->duration_years} years.";
        }

        $exists = Section::where('program_id', $program->id)
            ->where('semester_id', $semesterId)
            ->where('year_level', $data['year_level'])
            ->where('name', $data['name'])
            ->exists();

        if ($exists) {
            $errors[] = "Section '{$data['name']}' already exists for {$program->code}, year {$data['year_level']} in the selected semester.";
        }

        return $errors;
    }

    public static function updateValidation(array $data, array $context = []): array
    {
        $validator = Validator::make($data, self::$updateRules);

        $validator->after(function ($validator) use ($data) {
            if (empty($data)) {
                $validator->errors()->add('field', 'At least one field must be provided.');
            }
        });

        return $validator->errors()->all();
    }
}
