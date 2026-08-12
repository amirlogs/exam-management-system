<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $fillable = ['exam_attempt_id', 'selected_option_id', 'question_id','exam_question_id', 'answer_text', 'marks_awarded', 'graded_by'];

    public function examAttempt()
    {
        return $this->belongsTo(ExamAttempt::class);
    }

    public function examQuestion()
    {
        return $this->belongsTo(ExamQuestion::class);
    }

    public function grader()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
