<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportHistory extends Model
{
    protected $table = 'import_history';

    protected $fillable = [
        'uploaded_by', 'type', 'total_rows',
        'success_count', 'failure_count', 'status', 'errors',
    ];

    protected $casts = ['errors' => 'array'];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'import_batch_id');
    }
}
