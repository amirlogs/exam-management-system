<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamApproval extends Model
{
    protected $fillable = ['exam_id', 'cycle', 'reviewed_by', 'decision', 'reason'];


    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
