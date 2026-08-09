<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamApproval extends Model
{
    public $timestamps = false;

    protected $fillable = ['exam_id', 'approved_by', 'status', 'reason', 'approved_at'];

    protected $casts = ['approved_at' => 'datetime'];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
