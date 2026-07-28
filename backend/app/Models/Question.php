<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'course_id', 'created_by', 'type', 'chapter',
        'content', 'difficulty', 'status',
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
}
