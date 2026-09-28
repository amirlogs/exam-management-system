<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PracticeExamQuestion extends Model
{
    protected $fillable = [
        'practice_exam_id',
        'practice_question_id',
    ];
}
