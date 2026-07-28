<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionApproval extends Model
{
    public $timestamps = false;
    protected $fillable = ['question_id', 'approved_by', 'status', 'comment', 'approved_at'];
    protected $casts = ['approved_at' => 'datetime'];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
