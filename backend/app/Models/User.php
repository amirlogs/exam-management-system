<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['first_name', 'last_name', 'email', 'password', 'is_first_login'];

    protected $hidden = ['password'];

    protected $casts = ['is_first_login' => 'boolean'];

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /** All permission names this user holds across every role assignment. */
    public function permissionNames(): array
    {
        return $this->userRoles()
            ->with('role.permissions')
            ->get()
            ->pluck('role.permissions')
            ->flatten()
            ->pluck('name')
            ->unique()
            ->values()
            ->all();
    }

    public function workspaces(): array
    {
        $perms = collect($this->permissionNames());

        $adminIndicators = [
            'university.create', 'university.update', 'university.archive',
            'college.create', 'college.update', 'college.archive',
            'department.create', 'department.update', 'department.archive',
            'program.create', 'program.update', 'program.archive',
            'course.create', 'course.update', 'course.archive',
            'curriculum.create', 'curriculum.update', 'curriculum.archive', 'curriculum_version.activate',
            'curriculum_course.add', 'curriculum_course.remove', 'curriculum_course.update',
            'semester.create', 'semester.update', 'semester.open', 'semester.close', 'semester.archive',
            'student.import', 'instructor.import', 'section.import', 'class.import',
            'course_offering.create', 'course_offering.update', 'course_offering.approve', 'course_offering.reject',
            'course_offering.assign_instructor', 'course_offering.change_instructor', 'course_offering.cancel', 'course_offering.archive',
            'user.create', 'user.update', 'user.view', 'user.disable', 'user.activate', 'user.reset_password',
            'role.create', 'role.update', 'role.archive', 'role.assign', 'role.remove', 'permission.assign',
            'exam.approve', 'exam.publish', 'exam.reject', 'exam.schedule', 'exam.update_schedule', 'exam.extend_time', 'exam.close',
            'grade.verify', 'grade.publish', 'grade.unpublish',
            'result.view', 'result.generate', 'result.publish', 'result.export', 'result.print',
        ];

        $instructorIndicators = [
            'question.create', 'question.update', 'question.archive', 'question.restore', 'question.import', 'question.export',
            'exam.create', 'exam.update', 'exam.submit_approval',
            'grade.create', 'grade.update', 'grade.submit_verification',
        ];

        $studentIndicators = ['result.view_own'];

        return [
            'admin' => $perms->intersect($adminIndicators)->isNotEmpty(),
            'instructor' => $perms->intersect($instructorIndicators)->isNotEmpty(),
            'student' => $perms->intersect($studentIndicators)->isNotEmpty(),
        ];
    }

    public function hasWorkspace(string $workspace): bool
    {
        $workspaces = $this->workspaces();
        if (! $workspaces[$workspace]) {
            return false;
        }

        return true;
    }

    public function hasPermission(string $reqPermmision): bool
    {
        $permmisions = $this->permissionNames();

        return in_array($reqPermmision, $permmisions, true);
    }
}
