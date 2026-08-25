<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'course_offering_id', 'title', 'type', 'duration_minutes',
        'composition', 'status', 'review_cycle', 'current_review_id',
        'created_by', 'submitted_at', 'scheduled_start', 'scheduled_end',
        'total_marks', 'total_questions'
    ];
    
    protected $casts = [
        'composition' => 'array',
        'submitted_at' => 'datetime',
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
    ];

    public function courseOffering()
    {
        return $this->belongsTo(CourseOffering::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function examQuestions()
    {
        return $this->hasMany(ExamQuestion::class);
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'exam_questions')->withPivot('order_number', 'marks', 'id');
    }

    public function attempts()
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function approvals()
    {
        return $this->hasMany(ExamApproval::class);
    }
}
