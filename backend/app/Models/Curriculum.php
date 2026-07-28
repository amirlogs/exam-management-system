<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Curriculum extends Model
{
    protected $fillable = ['program_id', 'version', 'academic_year', 'status'];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function curriculumCourses()
    {
        return $this->hasMany(CurriculumCourse::class);
    }
}
