<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = ['exam_id', 'student_id', 'started_at', 'submitted_at', 'status', 'score'];

    protected $casts = ['started_at' => 'datetime', 'submitted_at' => 'datetime'];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function studentAnswers()
    {
        return $this->hasMany(Answer::class);
    }
    
}
