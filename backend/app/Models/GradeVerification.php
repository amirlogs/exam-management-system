<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeVerification extends Model
{
    public $timestamps = false;

    protected $fillable = ['grade_id', 'verified_by', 'status', 'comment', 'verified_at'];

    protected $casts = ['verified_at' => 'datetime'];

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
