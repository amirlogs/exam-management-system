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

    protected static function booted(): void
    {
        static::saved(function (PracticeQuestion $question) {
            $question->syncPracticeExamTotals();
        });

        static::deleted(function (PracticeQuestion $question) {
            $question->syncPracticeExamTotals();
        });
    }

    public function syncPracticeExamTotals(): void
    {
        if ($this->practice_exam_id) {
            $count = static::where('practice_exam_id', $this->practice_exam_id)->count();
            PracticeExam::where('id', $this->practice_exam_id)->update([
                'total_questions' => $count,
                'total_marks' => $count,
            ]);
        }
    }

    public function options()
    {
        return $this->hasMany(PracticeQuestionOption::class);
    }

    public function optionsWithoutAnswers()
    {
        return $this->hasMany(PracticeQuestionOption::class)->select(['id', 'practice_question_id', 'option_text']);
    }

    public function answers()
    {
        return $this->hasMany(PracticeAnswers::class, 'practice_question_id');
    }
}
