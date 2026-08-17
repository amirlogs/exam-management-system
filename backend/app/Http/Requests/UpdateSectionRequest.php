<?php

namespace App\Http\Requests;

use App\Traits\RequiresAtLeastOneField;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSectionRequest extends FormRequest
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
            'semester_id' => ['sometimes', 'integer', Rule::exists('semesters', 'id')->withoutTrashed()],
            'program_id' => ['sometimes', 'integer', 'exists:programs,id'],
            'year_level' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'name' => ['sometimes', 'integer', 'min:1', 'max:100',
                Rule::unique('sections')
                    ->ignore($this->section)
                    ->where(fn ($query) => $query
                        ->where('program_id', $this->program_id)
                        ->where('year_level', $this->year_level)),
            ],
        ];
    }
}
