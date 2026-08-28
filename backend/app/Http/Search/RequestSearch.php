<?php

namespace App\Http\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class RequestSearch
{
    public static function apply(Builder $query, Request $request, array $allowed): Builder
    {
        if (! $request->filled('search')) {
            return $query;
        }

        $search = $request->input('search');

        $query->where(function ($query) use ($allowed, $search) {
            foreach ($allowed as $key => $column) {
                if ($key === 0) {
                    $query->where($column, 'ILIKE', "%{$search}%");
                } else {
                    $query->orWhere($column, 'ILIKE', "%{$search}%");
                }
            }
        });

        return $query;
    }
}
