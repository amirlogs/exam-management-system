<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'course_id', 'created_by', 'type', 'chapter',
        'content', 'difficulty', 'status','import_history_id'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class);
    }

    public function approvals()
    {
        return $this->hasMany(QuestionApproval::class);
    }

    public function importHistory()
    {
        return $this->belongsTo(ImportHistory::class);
    }
}
