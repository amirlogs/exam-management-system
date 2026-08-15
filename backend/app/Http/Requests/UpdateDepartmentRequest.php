<?php

namespace App\Http\Requests;

use App\Traits\RequiresAtLeastOneField;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
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
            'college_id' => ['sometimes', 'integer'],
            'name' => ['sometimes', 'string', 'min:3', 'max:255', Rule::unique('departments')->ignore($this->department)],
            'type' => ['sometimes', 'string', 'max:255', Rule::in(['service_only', 'degree_granting'])],
        ];
    }
}
