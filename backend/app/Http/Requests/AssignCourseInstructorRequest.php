<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignCourseInstructorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'instructor_id' => ['required', 'integer', 'exists:instructors,id'],
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'type' => ['required', Rule::in(['lead_instructor', 'instructor']),
            ],
        ];
    }
}
