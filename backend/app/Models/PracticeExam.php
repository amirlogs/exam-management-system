<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PracticeExam extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'duration_minutes',
        'composition',
        'total_marks',
        'total_questions',
        'status',
        'owned_by',
        'ended_at',
    ];

    protected $casts = [
        'composition' => 'array',
    ];

    public function ownedBy()
    {
        return $this->belongsTo(User::class, 'owned_by');
    }

    public function practiceQuestions()
    {
        return $this->hasMany(PracticeQuestion::class, 'practice_exam_id');
    }

    public function recalculateTotals(): void
    {
        if ($this->id) {
            $count = $this->practiceQuestions()->count();

            $this->attributes['total_questions'] = $count;
            $this->attributes['total_marks'] = $count;

            static::where('id', $this->id)->update([
                'total_questions' => $count,
                'total_marks' => $count,
            ]);
        }
    }

    public function getTotalQuestionsAttribute(): int
    {
        if ($this->relationLoaded('practiceQuestions')) {
            return $this->practiceQuestions->count();
        }

        if (isset($this->attributes['practice_questions_count'])) {
            return (int) $this->attributes['practice_questions_count'];
        }

        if ($this->id) {
            $realCount = $this->practiceQuestions()->count();
            if ($realCount > 0 && $realCount !== (int) ($this->attributes['total_questions'] ?? 0)) {
                $this->recalculateTotals();

                return $realCount;
            }
        }

        return (int) ($this->attributes['total_questions'] ?? 0);
    }
}
