<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeQuestionHistory extends Model
{
    protected $fillable = [
        'owned_by',
        'input',
        'count',
        'isNote',
        'type',
        'difficulty',
        'file_path',
        'context',
        'total_rows',
        'valid_count',
        'error_count',
        'validated_question',
        'status',
        'practice_exam_id',
    ];

    protected $casts = [
        'isNote' => 'boolean',
        'context' => 'array',
        'validated_question' => 'array',
        'count' => 'integer',
        'total_rows' => 'integer',
        'valid_count' => 'integer',
        'error_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owned_by');
    }
}
