<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $fillable = ['student_id', 'course_offering_id', 'status', 'import_batch_id'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function courseOffering()
    {
        return $this->belongsTo(CourseOffering::class);
    }

    public function importBatch()
    {
        return $this->belongsTo(ImportHistory::class, 'import_batch_id');
    }
}
