<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class AddQuestionsRequest extends FormRequest
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
            'question_id' => ['required', 'exists:questions,id', 'unique:exam_questions,question_id'],
            'marks' => ['sometimes', 'numeric', 'min:0.5'],
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'question_id.required' => 'Please select a question',
            'question_id.exists' => 'The selected question does not exist',
            'question_id.unique' => 'The selected question is already added to the exam',
        ];
    }
}
