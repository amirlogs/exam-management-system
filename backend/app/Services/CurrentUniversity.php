<?php

namespace App\Services;

use App\Models\University;

class CurrentUniversity
{
    public static function id(): int
    {
        // Single-university mode:
        return University::query()->orderBy('id')->value('id');

        // Multi-tenant mode :
        // return auth()->user()->university_id;
    }
}
