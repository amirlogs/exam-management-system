<?php

namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class RequestFilters
{
    public static function apply(Builder $query, Request $request, array $allowed): Builder
    {
        foreach ($allowed as $key) {
            if ($request->filled($key)) {
                $query->where($key, $request->input($key));
            }
        }
        return $query;
    }
}
