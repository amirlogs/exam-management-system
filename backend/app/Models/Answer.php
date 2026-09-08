<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $fillable = [
        'exam_attempt_id',
        'selected_option_id',
        'question_id',
        'exam_question_id',
        'answer_text',
        'marks_awarded',
        'graded_by',
        'is_correct',
        'graded_at',
    ];

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

    public function selectedOption()
    {
        return $this->belongsTo(QuestionOption::class, 'selected_option_id');
    }

    public function isCorrect()
    {
        return $this->selectedOption?->is_correct ?? false;
    }

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function hasChosenOption()
    {
        return $this->selected_option_id != null;
    }
}
