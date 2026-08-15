<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSectionRequest extends FormRequest
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
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'year_level' => ['required', 'integer', 'min:1', 'max:10'],
            'name' => ['required', 'integer', 'min:1', 'max:100',
                Rule::unique('sections')
                    ->where(fn ($query) => $query
                        ->where('program_id', $this->program_id)
                        ->where('year_level', $this->year_level)
                    ),
            ],
        ];
    }
}
