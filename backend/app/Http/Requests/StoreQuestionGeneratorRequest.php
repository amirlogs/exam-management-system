<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionGeneratorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'input' => ['required', 'string', 'min:3'],
            'count' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'is_note' => ['sometimes', 'boolean'],
            'type' => ['sometimes', 'in:mcq,essay,true_false,short_answer'],
            'difficulty' => ['sometimes', 'in:easy,medium,hard'],
            'context' => ['sometimes', 'nullable'],
        ];
    }

    public function validatedInput(): array
    {
        $rawContext = $this->input('context');
        $context = is_array($rawContext)
            ? $rawContext
            : (is_string($rawContext) ? json_decode($rawContext, true) : null);

        return [
            'input' => $this->input('input'),
            'count' => (int) $this->input('count', 3),
            'isNote' => $this->boolean('is_note', false),
            'type' => $this->input('type', 'mcq'),
            'difficulty' => $this->input('difficulty', 'medium'),
            'context' => $context ?? [],
        ];
    }
}
