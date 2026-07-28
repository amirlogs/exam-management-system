<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseOffering extends Model
{
    protected $fillable = ['course_id', 'semester_id', 'created_by', 'status'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sections()
    {
        return $this->belongsToMany(Section::class, 'course_offering_sections');
    }

    public function instructors()
    {
        return $this->belongsToMany(User::class, 'course_instructors', 'course_offering_id', 'instructor_id')
            ->withPivot('type', 'assigned_at');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }
}
