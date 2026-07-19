<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionFlag extends Model
{
    //
    protected $fillable = [
        'question_id',
        'instructor_id',
        'comment',
        'status',
    ];
    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }
}
