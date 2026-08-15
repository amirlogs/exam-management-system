<?php

namespace App\Http\Requests;

use App\Traits\RequiresAtLeastOneField;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCurriculumRequest extends FormRequest
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
            'program_id' => ['sometimes', 'integer', 'exists:programs,id'],
            'version' => ['sometimes', 'string', Rule::unique('curricula', 'version')->ignore($this->curriculum->id)],
        ];
    }
}
