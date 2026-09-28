<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PracticeQuestion extends Model
{
    protected $fillable = [
        'owned_by',
        'practice_exam_id',
        'practice_question_histories_id',
        'type',
        'content',
        'difficulty',
        'status',
    ];

    public function options()
    {
        return $this->hasMany(PracticeQuestionOption::class);
    }

    public function optionsWithoutAnswers()
    {
        return $this->hasMany(PracticeQuestionOption::class)->select(['id','practice_question_id','option_text']);
    }

    public function answers()
    {
        return $this->hasMany(PracticeAnswers::class, 'practice_question_id');
    }
}
