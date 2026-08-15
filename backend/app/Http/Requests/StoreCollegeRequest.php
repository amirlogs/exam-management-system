<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCollegeRequest extends FormRequest
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
            // 'university_id' => ['required', 'integer', 'exists:universities,id'],
            'name' => ['required', 'string', 'min:3', 'max:255', Rule::unique('colleges', 'name')->ignore($this->college)],
        ];
    }

    public function messages()
    {
        return [
            // 'university_id.exists' => 'University not found.',
            'name.unique' => 'The college name has already been taken.'
        ];
    }
}
