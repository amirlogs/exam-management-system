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

    // public function examQuestions()
    // {
    //     return $this->hasMany(ExamQuestion::class);
    // }
    //
    // public function questions()
    // {
    //     return $this->belongsToMany(Question::class, 'exam_questions')->withPivot('order_number', 'marks', 'id');
    //}
}
