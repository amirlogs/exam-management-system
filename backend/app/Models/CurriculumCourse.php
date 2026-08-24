<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurriculumCourse extends Model
{
    protected $fillable = ['curriculum_id ', 'course_id', 'year_level', 'semester_number'];

    const UPDATED_AT = null;

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class)->withTrashed();
    }
}
