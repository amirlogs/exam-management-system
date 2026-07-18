<?php

namespace App\Validation;

use Illuminate\Support\Facades\Validator;

class ValidateQuestionRow
{
    public static function validate(array $data): array
    {
        $validator = Validator::make($data, [
            'type' => 'required|in:mcq,essay,true_false,short_answer',
            'text' => 'required|string|min:5',
            'options' => 'required_if:type,mcq,true_false|nullable',
            'correct_answer' => 'required_if:type,mcq,true_false|nullable|string',
            'difficulty' => 'required|in:easy,medium,hard',
            'points' => 'required|numeric|min:0.5|max:100',
        ]);

        $errors = $validator->errors()->all();
        $type = $data['type'] ?? null;

        // check the above rules only
        if (! in_array($type, ['mcq', 'true_false']) || empty($data['options'])) {
            return $errors;
        }

        $options = is_array($data['options'])
            ? $data['options']
            : json_decode($data['options'], true);

        if (! is_array($options) || json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = 'Options must be a valid list.';

            return $errors;
        }

        // check true false options
        if ($type === 'true_false') {
            $normalized = array_map('strtolower', $options);
            sort($normalized);
            if ($normalized !== ['false', 'true']) {
                $errors[] = 'True/false questions must have exactly the options "True" and "False".';
            }
        }

        if ($type === 'mcq') {
            $clean = array_filter(array_unique($options), fn ($o) => trim((string) $o) !== '');
            if (count($clean) < 2) {
                $errors[] = 'MCQ questions must have at least 2 distinct options.';
            }
        }

        // check correct answer to be in options
        //  
        if (! empty($data['correct_answer'])) {
            $answerExists = collect($options)
                ->map(fn ($o) => strtolower(trim((string) $o)))
                ->contains(strtolower(trim((string) $data['correct_answer'])));

            if (! $answerExists) {
                $errors[] = 'The correct answer must match one of the provided options.';
            }
        }

        return $errors;
    }
}
