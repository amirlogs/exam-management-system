<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionFlag extends Model
{
    protected $fillable = ['question_id', 'flagged_by', 'comment', 'status', 'resolved_by', 'resolved_at'];

    protected $casts = ['resolved_at' => 'datetime'];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function flagger()
    {
        return $this->belongsTo(User::class, 'flagged_by');
    }
}
