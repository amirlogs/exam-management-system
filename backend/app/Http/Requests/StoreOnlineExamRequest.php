<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOnlineExamRequest extends FormRequest
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
        // "MCQ": { "marks_each": 1 },
        //    "TRUE_FALSE": {"marks_each": 2 }

        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:MIDTERM,FINAL'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'composition' => ['required', 'array'],
            'composition.*.marks_each' => ['nullable', 'numeric', 'min:0.5'],
        ];
    }
}
