<?php

namespace App\Validation;

use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class GetRequestsValidator
{
    use ApiResponse;

    public static function validate($request, $max_per_page = 100)
    {
        $validator = Validator::make($request->all(), [
            'per_page' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $per_page = min($request->per_page ?? 12, $max_per_page);
        
        return $per_page;
    }
}
