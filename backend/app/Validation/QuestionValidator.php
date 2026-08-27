<?php

namespace App\Validation;

use Illuminate\Support\Facades\Validator;

class QuestionValidator
{
    private static array $rules = [
        'type' => ['required', 'in:mcq,essay,true_false,short_answer'],
        'content' => ['required', 'string', 'min:5'],
        'chapter' => ['nullable', 'string', 'max:255'],
        'options' => ['nullable'],
        'correct_answer' => ['nullable', 'string'],
        'difficulty' => ['required', 'in:easy,medium,hard'],
    ];

    private static array $updateRules = [
        'type' => ['sometimes', 'in:mcq,essay,true_false,short_answer'],
        'content' => ['sometimes', 'string', 'min:5'],
        'chapter' => ['nullable', 'string', 'max:255'],
        'options' => ['nullable'],
        'correct_answer' => ['nullable', 'string'],
        'difficulty' => ['sometimes', 'in:easy,medium,hard'],
    ];

    public static function validate(array $data, array $context = []): array
    {
        $data['type'] = strtolower(trim((string) ($data['type'] ?? '')));
        $validator = Validator::make($data, self::$rules);
        $errors = $validator->errors()->all();
        if (! empty($errors)) {
            return $errors;
        }
        $type = $data['type'];

        if (! in_array($type, ['mcq', 'true_false'], true)) {
            return $errors;
        }

        if (empty($data['options'])) {
            $errors[] = 'Options are required for MCQ and true/false questions.';

            return $errors;
        }

        $options = is_array($data['options']) ? $data['options'] : json_decode($data['options'], true);

        if (! is_array($options) || json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = 'Options must be a valid list.';

            return $errors;
        }

        if ($type === 'true_false') {
            $normalized = array_map(fn ($option) => strtolower(trim((string) $option)),
                $options
            );

            sort($normalized);

            if ($normalized !== ['false', 'true']) {
                $errors[] = 'True/false questions must have exactly the options "True" and "False".';
            }
        }

        if ($type === 'mcq') {
            $clean = array_filter(array_unique($options), fn ($option) => trim((string) $option) !== ''
            );

            if (count($clean) < 2) {
                $errors[] = 'MCQ questions must have at least 2 distinct options.';
            }
        }

        if (! empty($data['correct_answer'])) {
            $answerExists = collect($options)->map(fn ($option) => strtolower(trim((string) $option)))->contains(strtolower(trim((string) $data['correct_answer'])));

            if (! $answerExists) {
                $errors[] = 'The correct answer must match one of the provided options.';
            }
        }

        return $errors;
    }

    public static function updateValidation(array $data, array $context = []): array
    {
        if (isset($data['type'])) {
            $data['type'] = strtolower(trim((string) $data['type']));
        }

        $validator = Validator::make(
            $data,
            self::$updateRules
        );

        $validator->after(function ($validator) use ($data) {
            if (empty($data)) {
                $validator->errors()->add('field', 'At least one field must be provided.');
            }
        });

        $errors = $validator->errors()->all();

        $type = $data['type'] ?? null;

        if (! in_array($type, ['mcq', 'true_false'], true)) {
            return $errors;
        }

        if (empty($data['options'])) {
            $errors[] = 'Options are required for MCQ and true/false questions.';

            return $errors;
        }

        $options = is_array($data['options']) ? $data['options'] : json_decode($data['options'], true);

        if (! is_array($options) || json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = 'Options must be a valid list.';

            return $errors;
        }

        if ($type === 'true_false') {
            $normalized = array_map(fn ($option) => strtolower(trim((string) $option)), $options);

            sort($normalized);

            if ($normalized !== ['false', 'true']) {
                $errors[] = 'True/false questions must have exactly the options "True" and "False".';
            }
        }

        if ($type === 'mcq') {
            $clean = array_filter(array_unique($options), fn ($option) => trim((string) $option) !== '');

            if (count($clean) < 2) {
                $errors[] = 'MCQ questions must have at least 2 distinct options.';
            }
        }

        if (! empty($data['correct_answer'])) {
            $answerExists = collect($options)->map(fn ($option) => strtolower(trim((string) $option)))
                ->contains(strtolower(trim((string) $data['correct_answer'])));

            if (! $answerExists) {
                $errors[] = 'The correct answer must match one of the provided options.';
            }
        }

        return $errors;
    }

    public static function buildOptions(string $type, array|string $options, ?string $correctAnswer): array
    {
        $type = strtolower(trim($type));
        if (! in_array($type, ['mcq', 'true_false'], true)) {
            return [];
        }
        $options = is_array($options) ? $options : json_decode($options, true);
        $normalizedAnswer = strtolower(trim((string) $correctAnswer));

        return array_map(fn ($optionText) => ['option_text' => $optionText, 'is_correct' => strtolower(trim((string) $optionText)) === $normalizedAnswer], $options);
    }
}
