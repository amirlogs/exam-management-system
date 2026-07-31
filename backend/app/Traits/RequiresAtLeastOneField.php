<?php

namespace App\Traits;

trait RequiresAtLeastOneField
{
    public function after(): array
    {
        return [
            function ($validator) {
                if (empty($this->all())) {
                    $validator->errors()->add('field', 'At least one field must be provided.');
                }
            },
        ];
    }
}
