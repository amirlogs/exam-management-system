<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PracticeAnswers extends Model
{
    protected $table = 'practice_answers';

    protected $fillable = [
        'practice_exam_id',
        'practice_question_id',
        'practice_exam_question_id',
        'selected_option_id',
        'answer_text',
        'is_correct',
        'user_id',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function exam()
    {
        return $this->belongsTo(PracticeExam::class, 'practice_exam_id');
    }

    public function question()
    {
        return $this->belongsTo(PracticeQuestion::class, 'practice_question_id');
    }

    public function selectedOption()
    {
        return $this->belongsTo(PracticeQuestionOption::class, 'selected_option_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

class_alias(PracticeAnswers::class, PracticeAnswer::class);

