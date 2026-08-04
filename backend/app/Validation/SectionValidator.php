<?php

namespace App\Validation;

use App\Models\Program;
use App\Models\Section;
use Illuminate\Support\Facades\Validator;

class SectionValidator
{
    private static array $rules = [
        'program_code' => ['required', 'string', 'exists:programs,code'],
        'year_level' => ['required', 'integer', 'min:1', 'max:8'],
        'name' => ['required', 'string', 'min:1', 'max:50'],
    ];

    protected static array $messages = [
        'program_code.required' => 'The program code field is required.',
        'program_code.exists' => 'The selected program code is invalid.',
        'year_level.required' => 'The year level field is required.',
        'year_level.integer' => 'The year level must be an integer.',
        'year_level.min' => 'The year level must be at least 1.',
        'year_level.max' => 'The year level may not be greater than 8.',
        'name.required' => 'The section name field is required.',
        'name.max' => 'The section name may not be greater than 50 characters.',
    ];

    public static function validate(array $data, array $context = [])
    {
        $validator = Validator::make($data, self::$rules, self::$messages);
        $errors = $validator->errors()->all();

        // if (empty($errors)) {
        //     $semesterId = $context['semester_id'] ?? null;

        //     if (! $semesterId) {
        //         $errors[] = 'No semester_id was provided for this import batch.';
        //     } else {
        //         $program = Program::where('code', $data['program_code'])->first();

        // $duplicate = Section::where('program_id', $program->id)
        //     ->where('semester_id', $semesterId)
        //     ->where('year_level', $data['year_level'])
        //     ->where('name', $data['name'])
        //     ->exists();

        //         if ($duplicate) {
        //             $errors[] = "Section '{$data['name']}' already exists for program '{$data['program_code']}' in this semester.";
        //         }
        //     }
        // }
        return $errors;
    }
}
