<?php

namespace App\Validation;

use Illuminate\Support\Facades\Validator;

class QuestionValidator
{
    private static array $rules = [
        //change the type to lower 
        
        'type'=> ['required', 'in:mcq,essay,true_false,short_answer'],
        'content' => ['required', 'string', 'min:5'],
        'chapter' => ['nullable', 'string', 'max:255'],
        'options' => ['required_if:type,MCQ,TRUE_FALSE', 'nullable'],
        'correct_answer' => ['required_if:type,MCQ,TRUE_FALSE', 'nullable', 'string'],
        'difficulty' => ['required', 'in:easy,medium,hard'],
    ];

    private static array $updateRules = [
        'type' => ['sometimes', 'in:mcq,essay,true_false,short_answer'],
        'content' => ['sometimes', 'string', 'min:5'],
        'chapter' => ['nullable', 'string', 'max:255'],
        'options' => ['required_if:type,MCQ,TRUE_FALSE', 'nullable'],
        'correct_answer' => ['required_if:type,MCQ,TRUE_FALSE', 'nullable', 'string'],
        'difficulty' => ['sometimes', 'in:easy,medium,hard'],
    ];

    protected static array $messages = [];

    protected static array $updateMessages = [];

    public static function validate(array $data, array $context = []): array
    {
        $validator = Validator::make($data, self::$rules, self::$messages);

        $errors = $validator->errors()->all();
        $type = $data['type'] ?? null;

        if (! in_array($type, ['MCQ', 'TRUE_FALSE']) || empty($data['options'])) {
            return $errors;
        }

        $options = is_array($data['options'])
            ? $data['options']
            : json_decode($data['options'], true);

        if (! is_array($options) || json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = 'Options must be a valid list.';

            return $errors;
        }

        if ($type === 'TRUE_FALSE') {
            $normalized = array_map('strtolower', $options);
            sort($normalized);
            if ($normalized !== ['false', 'true']) {
                $errors[] = 'True/false questions must have exactly the options "True" and "False".';
            }
        }

        if ($type === 'MCQ') {
            $clean = array_filter(array_unique($options), fn ($o) => trim((string) $o) !== '');
            if (count($clean) < 2) {
                $errors[] = 'MCQ questions must have at least 2 distinct options.';
            }
        }

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

    public static function updateValidation(array $data): array
    {
        $validator = Validator::make($data, self::$updateRules, self::$updateMessages);

        $validator->after(function ($validator) use ($data) {
            if (empty($data)) {
                $validator->errors()->add(
                    'field',
                    'At least one field must be provided.'
                );
            }
        });

        $errors = $validator->errors()->all();
        $type = $data['type'] ?? null;

        if ($type && (! in_array($type, ['MCQ', 'TRUE_FALSE']) || empty($data['options']))) {
            return $errors;
        }

        if (! empty($data['options'])) {
            $options = is_array($data['options'])
                ? $data['options']
                : json_decode($data['options'], true);

            if (! is_array($options) || json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = 'Options must be a valid list.';

                return $errors;
            }

            if ($type === 'TRUE_FALSE') {
                $normalized = array_map('strtolower', $options);
                sort($normalized);
                if ($normalized !== ['false', 'true']) {
                    $errors[] = 'True/false questions must have exactly the options "True" and "False".';
                }
            }

            if ($type === 'MCQ') {
                $clean = array_filter(array_unique($options), fn ($o) => trim((string) $o) !== '');
                if (count($clean) < 2) {
                    $errors[] = 'MCQ questions must have at least 2 distinct options.';
                }
            }

            if (! empty($data['correct_answer'])) {
                $answerExists = collect($options)
                    ->map(fn ($o) => strtolower(trim((string) $o)))
                    ->contains(strtolower(trim((string) $data['correct_answer'])));

                if (! $answerExists) {
                    $errors[] = 'The correct answer must match one of the provided options.';
                }
            }
        }

        return $errors;
    }

    public static function buildOptions(string $type, array|string $options, ?string $correctAnswer): array
    {
        if (! in_array($type, ['MCQ', 'TRUE_FALSE'])) {
            return [];
        }

        $options = is_array($options) ? $options : json_decode($options, true);
        $normalizedAnswer = strtolower(trim((string) $correctAnswer));

        return array_map(fn ($optionText) => [
            'option_text' => $optionText,
            'is_correct' => strtolower(trim((string) $optionText)) === $normalizedAnswer,
        ], $options);
    }
}
