<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use SoftDeletes;

    protected $fillable = ['department_id', 'code', 'name', 'credit_hours'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function courseOfferings()
    {
        return $this->hasMany(CourseOffering::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
