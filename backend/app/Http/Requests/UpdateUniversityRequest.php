<?php

namespace App\Http\Requests;

use App\Traits\RequiresAtLeastOneField;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUniversityRequest extends FormRequest
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
        // name and code must be unique in the universities table except for the current university
        return [
            'name' => ['string', 'min:3', 'max:255', 'sometimes', Rule::unique('universities')->ignore($this->university)],
            'code' => ['string', 'min:2', 'max:255',  'sometimes', Rule::unique('universities')->ignore($this->university)],
            'address' => ['string', 'min:3', 'max:255', 'sometimes'],
        ];
    }
}
