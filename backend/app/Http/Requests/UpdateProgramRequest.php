<?php

namespace App\Http\Requests;

use App\Traits\RequiresAtLeastOneField;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class UpdateProgramRequest extends FormRequest
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
            'department_id' => ['sometimes', 'integer', 'esists:departments,id'],
            'name' => ['sometimes', 'string', 'max:255', 'unique:programs,name'],
            'duration_years' => ['sometimes', 'integer', 'min:1', 'max:30'],
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'department_id.exists' => 'Department not found.',
        ];
    }
}
