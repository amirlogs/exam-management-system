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
            'count' => ['required', 'integer', 'min:1', 'max:20'],
            'is_note' => ['required', 'boolean'],
            'type' => ['required', 'in:mcq,essay,true_false,short_answer'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
        ];
    }
}
