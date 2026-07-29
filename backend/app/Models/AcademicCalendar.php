<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicCalendar extends Model
{
    protected $fillable = ['university_id', 'name', 'start_date', 'end_date', 'important_dates', 'status'];

    protected $casts = ['start_date' => 'date', 'end_date' => 'date', 'important_dates' => 'array'];

    public function university()
    {
        return $this->belongsTo(University::class);
    }
}
