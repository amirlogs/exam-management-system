<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradingSystem extends Model
{
    protected $fillable = ['university_id', 'name', 'scale', 'status'];
    protected $casts = ['scale' => 'array'];

    public function university()
    {
        return $this->belongsTo(University::class);
    }
}
