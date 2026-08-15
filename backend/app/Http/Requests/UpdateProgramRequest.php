<?php

namespace App\Http\Requests;

use App\Traits\RequiresAtLeastOneField;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
            'code' => ['sometimes', 'string', 'max:255', 'min:2', Rule::unique('programs')->ignore($this->program)],
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('programs')->ignore($this->program)],
            'duration_years' => ['sometimes', 'integer', 'min:1', 'max:10'],
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
