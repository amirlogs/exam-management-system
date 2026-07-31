<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class AssignRoleRequest extends FormRequest
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
            'role_id' => ['required', 'exists:roles,id'],
            'university_id' => ['sometimes', 'exists:universities,id'],
            'college_id' => ['sometimes', 'exists:colleges,id'],
            'department_id' => ['sometimes', 'exists:departments,id'],
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'role_id.unique' => 'This role is already assigned to the user.',
        ];
    }
}
