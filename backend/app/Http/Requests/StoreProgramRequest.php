<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class StoreProgramRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'code' => ['required', 'string', 'max:255', 'min:2', 'unique:programs,code'],
            'name' => ['required', 'string', 'max:255', 'min:3', 'unique:programs,name'],
            'duration_years' => ['required', 'integer', 'min:1', 'max:10'],
        ];

    }

    #[Override]
    public function messages()
    {
        return [
            'department_id.exists' => 'Department not Found',
        ];
    }
}
