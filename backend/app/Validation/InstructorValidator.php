<?php

namespace App\Validation;

use App\Models\Instructor;
use Illuminate\Support\Facades\Validator;

class InstructorValidator
{
    private static array $rules = [
        'first_name' => ['required', 'string', 'min:3', 'max:255'],
        'last_name' => ['required', 'string', 'min:3', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        'department_id' => ['required', 'integer', 'exists:departments,id'],
        'employee_number' => ['required', 'string', 'max:255'],
        'academic_rank' => ['required', 'string', 'max:255'],
    ];

    private static array $updateRules = [
        'first_name' => ['sometimes', 'string', 'min:3', 'max:255'],
        'last_name' => ['sometimes', 'string', 'min:3', 'max:255'],
        'email' => ['sometimes', 'string', 'email', 'max:255'],
        'department_id' => ['sometimes', 'integer', 'exists:departments,id'],
        'employee_number' => ['sometimes', 'string', 'max:255'],
        'academic_rank' => ['sometimes', 'string', 'max:255'],
    ];

    public static function validate(array $data, array $context = []): array
    {
        $validator = Validator::make($data, self::$rules);
        $errors = $validator->errors()->all();

        if (empty($errors) && Instructor::where('employee_number', $data['employee_number'])->exists()
        ) {
            $errors[] = "Employee number '{$data['employee_number']}' already exists.";
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
