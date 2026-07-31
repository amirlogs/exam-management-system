<?php

namespace App\Http\Requests;

use App\Traits\RequiresAtLeastOneField;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class UpdateCourseRequest extends FormRequest
{
    use RequiresAtLeastOneField;

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
            'department_id' => ['sometimes', 'integer', 'exists:departments,id'],
            'code' => ['sometimes', 'string', 'min:3', 'max:255', 'unique:courses,code'],
            'name' => ['sometimes', 'string', 'min:3', 'max:255', 'unique:courses,name'],
            'credit_hours' => ['sometimes', 'integer', 'min:1', 'max:100'],
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
