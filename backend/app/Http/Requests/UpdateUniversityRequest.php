<?php

namespace App\Http\Requests;

use App\Traits\RequiresAtLeastOneField;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'name' => ['string', 'min:3', 'max:255', 'unique:App\Models\University,name', 'sometimes'],
            'code' => ['string', 'min:2', 'max:255,unique:App\Models\University,code', 'sometimes'],
            'address' => ['string', 'min:3', 'max:255', 'sometimes'],
        ];
    }
    
}
