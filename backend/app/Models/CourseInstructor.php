<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseInstructor extends Model
{
    public $timestamps = false;

    protected $fillable = ['course_offering_id', 'instructor_id', 'section_id', 'type', 'assigned_at'];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function courseOffering()
    {
        return $this->belongsTo(CourseOffering::class);
    }

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}
