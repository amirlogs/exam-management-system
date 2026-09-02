<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Instructor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'department_id',
        'employee_number',
        'academic_rank',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function courseOfferings()
    {
        return $this->belongsToMany(CourseOffering::class, 'course_instructors', 'instructor_id', 'course_offering_id')->withPivot(['section_id', 'type', 'assigned_at']);
    }



    public function assignments()
    {
        return $this->hasMany(CourseInstructor::class);
    }
}
