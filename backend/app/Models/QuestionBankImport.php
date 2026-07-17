<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionBankImport extends Model
{
    protected $fillable = [
        'course_id',
        'status',
        'uploaded_by',
        'file_path',
    ];
}
