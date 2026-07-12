<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    protected $fillable = ["home_department_id", "year_level", "section_label"];

    public function homeDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, "home_department_id");
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, "section_id");
    }
}
