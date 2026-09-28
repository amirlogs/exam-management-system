<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionGuidances extends Model
{
    protected $fillable = [
        'user_id',
        'student_id',
        'practice_exam_id',
        'practice_question_id',
        'prompt',
        'response',
    ];
    public function practiceQuestion()
    {
        return $this->belongsTo(PracticeQuestion::class);
    }
}

class_alias(QuestionGuidances::class, QuestionGuidance::class);
