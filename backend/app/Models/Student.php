<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class Student extends Model
{
    protected $fillable = [
        'user_id', 'student_number', 'program_id',
        'section_id', 'curriculum_id', 'entry_year', 'status',
    ];

    protected static function booted(): void
    {
        // curriculum_id is immutable once set — no automatic migration allowed.
        static::updating(function (Student $student) {
            if ($student->isDirty('curriculum_id') && $student->getOriginal('curriculum_id') !== null) {
                throw new RuntimeException('curriculum_id is immutable once assigned.');
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function examAttempts()
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }
}
