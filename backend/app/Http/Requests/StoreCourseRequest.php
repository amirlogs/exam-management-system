<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class StoreCourseRequest extends FormRequest
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
            'code' => ['required', 'string', 'min:3', 'max:255', 'unique:courses,code'],
            'name' => ['required', 'string', 'min:3', 'max:255', 'unique:courses,name'],
            'credit_hours' => ['required', 'integer', 'min:1', 'max:30'],
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'department_id.exists' => 'Department not found',
        ];
    }
}
