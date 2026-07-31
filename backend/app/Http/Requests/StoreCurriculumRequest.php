<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class StoreCurriculumRequest extends FormRequest
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
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'version' => ['required', 'string', 'unique:curricula,version'],
            'academic_year' => ['required', 'integer', 'min:2025', 'max:5000', 'unique:curricula,academic_year'],
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'program_id.exists' => 'Program not exist.',
        ];
    }
}
