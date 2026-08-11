<?php

namespace App\Validation;

use Illuminate\Support\Facades\Validator;

class SubmitNonOptionAnswerValidator
{
    protected static array $nonOptionRules = [
        'exam_question_id' => ['required', 'integer', 'exists:exam_questions,id'],
        'answer_text' => ['required', 'string'],
    ];

    protected static array $nonOptionMessages = [
        'exam_question_id.required' => 'The question id is required.',
        'exam_question_id.integer' => 'The question id must be an integer.',
        'exam_question_id.exists' => 'The question id does not exist.',
        'answer_text.required' => 'The answer text is required.',
        'answer_text.string' => 'The answer text must be a string.',
    ];

    public static function validate(array $data)
    {
        $validator = Validator::make($data, self::$nonOptionRules, self::$nonOptionMessages);

        return $validator->errors()->all();
    }
}
