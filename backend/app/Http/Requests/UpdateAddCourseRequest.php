<?php

namespace App\Http\Requests;

use App\Traits\RequiresAtLeastOneField;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAddCourseRequest extends FormRequest
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
            'year_level' => ['required', 'integer', 'min:1', 'max:10'],
            'semester_number' => ['required', 'integer', 'min:1', 'max:2'],
        ];
    }
}
