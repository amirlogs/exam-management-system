<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreImportRequest extends FormRequest
{
    public function validationData(): array
    {
        return array_merge(
            $this->all(),
            ['type' => $this->route('type')]);
    }

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

        // type varchar [note: "students | instructors | sections | questions | enrollments"]
        return [
            'file' => ['required', 'file', 'mimes:csv,xlsx,xl', 'max:2048'],
            'type' => ['required', 'string', 'in:students,instructors,sections,questions,enrollments'],
            'context' => ['required', 'json'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'The type parameter must be one of the following: students, instructors, sections, questions, enrollments',
        ];
    }
}
