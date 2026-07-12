<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = ["name", "type", "head_user_id"];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, "home_department_id");
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, "owning_department_id");
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, "head_user_id");
    }
}
