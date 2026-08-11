<?php

namespace App\Validation;

use Illuminate\Support\Facades\Validator;


class SubmitOptionAnswerValidator
{
    protected static array $optionsRules = [
        'exam_question_id' => ['required', 'integer', 'exists:exam_questions,id'],
        'selected_option_id' => ['required', 'integer', 'exists:question_options,id'],
    ];

    protected static array $optionsMessages = [
        'exam_question_id.required' => 'The question id is required.',
        'exam_question_id.integer' => 'The question id must be an integer.',
        'exam_question_id.exists' => 'The question id does not exist.',
        'option_id.required' => 'The option id is required.',
        'option_id.integer' => 'The option id must be an integer.',
        'option_id.exists' => 'The option id does not exist.',
    ];

    public static function validate(array $data)
    {
        info("here its inside options ");
        $validator = Validator::make($data, self::$optionsRules, self::$optionsMessages);

        return $validator->errors()->all();
    }
}
