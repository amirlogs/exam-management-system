<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Curriculum extends Model
{
    protected $fillable = ['program_id', 'version', 'academic_year', 'status'];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function curriculumCourses()
    {
        return $this->hasMany(CurriculumCourse::class);
    }

    public function isActive()
    {
        return $this->status === 'active';
    }
    
    public function activate(): void
    {
    DB::transaction(function () {
            static::where('program_id', $this->program_id)
                ->where('status', 'active')
                ->update(['status' => 'archived']);
            $this->update(['status' => 'active']);
        });
    }
}
    