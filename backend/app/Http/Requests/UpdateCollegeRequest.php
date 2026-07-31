<?php

namespace App\Http\Requests;

use App\Traits\RequiresAtLeastOneField;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCollegeRequest extends FormRequest
{
    use RequiresAtLeastOneField;

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
            'university_id' => ['sometimes', 'integer', 'exists:universities,id'],
            'name' => ['sometimes', 'string', 'min:3', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            'university_id.exists' => 'University not found.',
        ];
    }
}
