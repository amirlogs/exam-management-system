<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class StoreDepartmentRequest extends FormRequest
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
            'college_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'min:3', 'max:255', 'unique:departments,name'],
            'type' => ['required', 'string', 'max:255', Rule::in(['service_only', 'degree_granting'])],
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'college_id.required' => 'The college field is required.',
            'college_id.integer' => 'The college field must be an integer.',
            'type.in' => 'The type field must be either service_only or degree_granting.',
        ];
    }
}
