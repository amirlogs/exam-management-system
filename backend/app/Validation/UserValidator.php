<?php

namespace App\Validation;

use Illuminate\Support\Facades\Validator;

class UserValidator
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

    public static function validate(array $data)
    {
        $validator = Validator::make($data, self::$rules, self::$messages);

        return $validator->errors()->all();
    }
}
