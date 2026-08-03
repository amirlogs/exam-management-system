<?php

namespace App\Validation;

use Illuminate\Support\Facades\Validator;

class StudentValidator
{
    // student_number,first_name,last_name,email,program_code,section_name,entry_year
    // ASTU/2026/0002,Selam,Wolde,selam.wolde@astu.edu,SE,1,2026
    // ASTU/2026/0003,Biniam,Tadesse,biniam.tadesse@astu.edu,SE,1,2026
    // ASTU/2026/0004,Marta,Kebede,marta.kebede@astu.edu,EE,1,2026
    // ASTU/2026/0005,Yared,Assefa,,SE,2,2026

    private static $rules =
        [
            'student_number' => ['required', 'string', 'min:8', 'max:20'],
            'first_name' => ['required', 'string', 'min:3', 'max:255'],
            'last_name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'program_code' => ['required', 'string', 'min:2', 'max:255'],
            'section_name' => ['required', 'string', 'min:1', 'max:255'],
            'entry_year' => ['required', 'integer', 'min:2020'],
        ];

    protected static array $messages =
        [
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
            'program_code.min' => 'The program code must be at least 3 characters.',
            'program_code.max' => 'The program code may not be greater than 255 characters.',
            'section_name.required' => 'The section name field is required.',
            'section_name.min' => 'The section name must be at least 1 characters.',
            'section_name.max' => 'The section name may not be greater than 255 characters.',
            'entry_year.required' => 'The entry year field is required.',
            'entry_year.integer' => 'The entry year must be an integer.',
            'entry_year.min' => 'The entry year must be at least 2020.',
        ];

    public static function validate(array $data)
    {
        $validator = Validator::make($data, self::$rules, self::$messages);

        return $validator->errors()->all();
    }
}
