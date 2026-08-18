<?php

namespace App\Validation;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class InstructorValidator
{
    private static array $rules = [
        'first_name' => ['required', 'string', 'min:3', 'max:255'],
        'last_name' => ['required', 'string', 'min:3', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
    ];

    protected static array $messages = [
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
    ];

    private static array $updateRules = [
        'first_name' => ['sometimes', 'string', 'min:3', 'max:255'],
        'last_name' => ['sometimes', 'string', 'min:3', 'max:255'],
        'email' => ['sometimes', 'string', 'email', 'max:255',],
    ];

    protected static array $updateMessages = [
        'first_name.min' => 'The first name must be at least 3 characters.',
        'first_name.max' => 'The first name may not be greater than 255 characters.',
        'last_name.min' => 'The last name must be at least 3 characters.',
        'last_name.max' => 'The last name may not be greater than 255 characters.',
        'email.email' => 'The email must be a valid email address.',
        'email.max' => 'The email may not be greater than 255 characters.',
        'email.unique' => 'The email has already been taken.',
    ];

    public static function validate(array $data)
    {
        $validator = Validator::make($data, self::$rules, self::$messages);

        return $validator->errors()->all();
    }

    public static function updateValidation(array $data)
    {
        $rules = self::$updateRules;
        $emailRule =  Rule::unique('users')->ignore(auth()->user()->id);
        $validator = Validator::make($data, [...$rules , 'email' => $emailRule], self::$updateMessages);

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
