<?php

namespace App\Validation;

use App\Models\Program;
use App\Models\Section;
use App\Models\Student;
use App\Traits\RequiresAtLeastOneField;
use Illuminate\Support\Facades\Validator;

class StudentValidator
{
    use RequiresAtLeastOneField;

    private static array $rules = [
        'student_number' => ['required', 'string', 'min:8', 'max:20'],
        'first_name' => ['required', 'string', 'min:3', 'max:255'],
        'last_name' => ['required', 'string', 'min:3', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        'program_code' => ['required', 'string', 'exists:programs,code'],
        'section_name' => ['required', 'string', 'min:1', 'max:255'],
        'entry_year' => ['required', 'integer', 'min:2020'],
    ];

    private static array $updateRules = [
        'student_number' => ['sometimes', 'string', 'min:8', 'max:20'],
        'first_name' => ['sometimes', 'string', 'min:3', 'max:255'],
        'last_name' => ['sometimes', 'string', 'min:3', 'max:255'],
        'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email'],
        'program_code' => ['sometimes', 'string', 'exists:programs,code'],
        'section_name' => ['sometimes', 'string', 'min:1', 'max:255'],
        'entry_year' => ['sometimes', 'integer', 'min:2020'],
    ];

    protected static array $messages = [
        'student_number.required' => 'The student number field is required.',
        'student_number.min' => 'The student number must be at least 8 characters.',
        'student_number.max' => 'The student number may not be greater than 20.',
        'first_name.required' => 'The first name field is required.',
        'first_name.min' => 'The first name must be at least 3 characters.',
        'first_name.max' => 'The first name may not be greater than 255 characters.',
        'last_name.required' => 'The last name field is required.',
        'last_name.min' => 'The last name must be at least 3 characters.',
        'last_name.max' => 'The last name may not be greater than 255 characters.',
        'email.required' => 'The email field is required.',
        'email.email' => 'The email must be a valid email address.',
        'email.max' => 'The email may not be greater than 255 characters.',
        'email.unique' => 'The email has already been taken.',
        'program_code.required' => 'The program code field is required.',
        'program_code.exists' => 'The selected program code is invalid.',
        'section_name.required' => 'The section name field is required.',
        'section_name.min' => 'The section name must be at least 1 characters.',
        'section_name.max' => 'The section name may not be greater than 255 characters.',
        'entry_year.required' => 'The entry year field is required.',
        'entry_year.integer' => 'The entry year must be an integer.',
        'entry_year.min' => 'The entry year must be at least 2020.',
    ];

    protected static array $updateMessages = [
        'student_number.required' => 'The student number field is required.',
        'student_number.min' => 'The student number must be at least 8 characters.',
        'student_number.max' => 'The student number may not be greater than 20.',
        'first_name.min' => 'The first name must be at least 3 characters.',
        'first_name.max' => 'The first name may not be greater than 255 characters.',
        'last_name.min' => 'The last name must be at least 3 characters.',
        'last_name.max' => 'The last name may not be greater than 255 characters.',
        'email.email' => 'The email must be a valid email address.',
        'email.max' => 'The email may not be greater than 255 characters.',
        'email.unique' => 'you have setten this email before update or remove it.',
        'program_code.exists' => 'The selected program code is invalid.',
        'section_name.min' => 'The section name must be at least 1 characters.',
        'section_name.max' => 'The section name may not be greater than 255 characters.',
        'entry_year.integer' => 'The entry year must be an integer.',
        'entry_year.min' => 'The entry year must be at least 2020.',
    ];

    public static function validate(array $data, array $context = [])
    {
        $validator = Validator::make($data, self::$rules, self::$messages);
        $errors = $validator->errors()->all();

        if (
            empty($errors) &&
            Student::where('student_number', $data['student_number'])->exists()
        ) {
            $errors[] = "Student number '{$data['student_number']}' already exists.";
        }

        if (empty($errors)) {
            $program = Program::where('code', $data['program_code'])->first();
            $semesterId = $context['semester_id'] ?? null;
            $yearLevel = $context['year_level'] ?? null;

            if (! $semesterId) {
                $errors[] = 'No semester_id was provided for this import batch.';
            }

            if (! $yearLevel) {
                $errors[] = 'No year_level was provided for this import batch.';
            }

            if ($semesterId && $yearLevel) {
                if ((int) $yearLevel > $program->duration_years) {
                    $errors[] = "Year level {$yearLevel} exceeds {$data['program_code']}'s program length of {$program->duration_years} years.";
                }

                if (! $program->curriculums()->where('status', 'active')->exists()) {
                    $errors[] = "Program '{$data['program_code']}' has no active curriculum.";
                }

                $sectionExists = Section::where('program_id', $program->id)
                    ->where('semester_id', $semesterId)
                    ->where('year_level', $yearLevel)
                    ->where('name', $data['section_name'])
                    ->exists();

                if (! $sectionExists) {
                    $errors[] = "Section '{$data['section_name']}' does not exist for program '{$data['program_code']}', year {$yearLevel}, in the selected semester.";
                }
            }
        }

        return $errors;
    }

    public static function UpdateValidation(array $data, array $context = [])
    {
        $validator = Validator::make($data, self::$updateRules, self::$updateMessages);

        $validator->after(function ($validator) use ($data) {
            if (empty($data)) {
                $validator->errors()->add(
                    'field',
                    'At least one field must be provided.'
                );
            }
        });

        return $validator->errors()->all();
    }
}
