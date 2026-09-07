<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddQuestionsBulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question_id' => ['required', 'exists:questions,id', 'distinct'],
            'questions.*.marks' => ['sometimes', 'nullable', 'numeric', 'min:0.5'],
        ];
    }

    public function messages()
    {
        return [
            'questions.*.question_id.required' => 'Please select a question.',
            'questions.*.question_id.exists' => 'One of the selected questions does not exist.',
            'questions.*.question_id.distinct' => 'The same question was selected more than once.',
        ];
    }
}
