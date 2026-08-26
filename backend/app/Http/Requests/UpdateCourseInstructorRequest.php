<?php

namespace App\Http\Requests;

use App\Traits\RequiresAtLeastOneField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseInstructorRequest extends FormRequest
{
    use RequiresAtLeastOneField;
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'instructor_id' => ['sometimes', 'integer', 'exists:instructors,id'],
            'section_id' => ['sometimes', 'integer', 'exists:sections,id'],
            'type' => ['sometimes', Rule::in(['lead_instructor', 'instructor'])],
        ];
    }
}
