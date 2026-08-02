<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportHistory extends Model
{
    protected $table = 'import_histories';

    protected $fillable = [
        'uploaded_by', 'type', 'file_path', 'context',
        'total_rows', 'valid_count', 'error_count', 'validated_data', 'status',
    ];

    protected $casts = [
        'context' => 'array',
        'validated_data' => 'array',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
