<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAnswerRequest extends FormRequest
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
        // {
        //   "exam_question_id": 5,
        //   "answer_text": "Structured Query Language"
        // }
        return [
            'exam_question_id' => 'required|integer',
            'answer_text' => 'required|string',
        ];
    }
}
