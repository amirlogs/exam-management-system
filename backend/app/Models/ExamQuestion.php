<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamQuestion extends Model
{
    public $timestamps = false;

    protected $fillable = ['exam_id', 'question_id', 'marks', 'order_number'];

    protected $casts = [
        'marks' => 'float',
        'order_number' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(function (ExamQuestion $examQuestion) {
            $examQuestion->syncExamTotals();
        });

        static::deleted(function (ExamQuestion $examQuestion) {
            $examQuestion->syncExamTotals();
        });
    }

    public function syncExamTotals(): void
    {
        if ($this->exam_id) {
            $questions = static::where('exam_id', $this->exam_id)->get();
            Exam::where('id', $this->exam_id)->update([
                'total_questions' => $questions->count(),
                'total_marks' => (int) $questions->sum('marks'),
            ]);
        }
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }
}
