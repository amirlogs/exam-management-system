<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    protected $fillable = ['academic_year', 'name', 'start_date', 'end_date', 'status'];

    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    public function courseOfferings()
    {
        return $this->hasMany(CourseOffering::class);
    }
}
