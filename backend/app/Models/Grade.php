<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = ['student_id', 'course_offering_id', 'exam_id', 'score', 'graded_by', 'status'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function courseOffering()
    {
        return $this->belongsTo(CourseOffering::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function grader()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function verifications()
    {
        return $this->hasMany(GradeVerification::class);
    }
}
