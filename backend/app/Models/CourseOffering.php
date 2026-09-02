<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseOffering extends Model
{
    use SoftDeletes;

    protected $fillable = ['course_id', 'semester_id', 'created_by', 'status', 'rejection_reason'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sections()
    {
        return $this->belongsToMany(Section::class, 'course_offering_sections');
    }

    public function instructors()
    {
        return $this->belongsToMany(Instructor::class, 'course_instructors', 'course_offering_id', 'instructor_id')->withPivot(['section_id', 'type', 'assigned_at']);
    }

    public function courseInstructors()
    {
        return $this->hasMany(CourseInstructor::class);
    }

    public function approve(): void
    {
        $this->update(['status' => 'approved']);
    }

    public function reject(string $reason): void
    {
        $this->update(['status' => 'rejected', 'rejection_reason' => $reason]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }
}
