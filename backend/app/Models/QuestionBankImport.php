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
        'valid_count',
        'error_count',
        'failure_reason',
        'confirmed_by',
        'confirmed_at',
        'approved_by',
        'approved_at',
        'validated_data',
    ];
}
