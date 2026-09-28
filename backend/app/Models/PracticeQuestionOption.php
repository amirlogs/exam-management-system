<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PracticeQuestionOption extends Model
{
    public $timestamps = false;

    protected $fillable = ['practice_question_id', 'option_text', 'is_correct'];

    protected $casts = ['is_correct' => 'boolean'];

    public function practiceQuestion()
    {
        return $this->belongsTo(PracticeQuestion::class);
    }
}
