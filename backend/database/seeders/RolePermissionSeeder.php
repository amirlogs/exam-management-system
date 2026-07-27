<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ===== SUPER ADMIN - System-level administrative only =====
        $superAdmin = Role::where('name', 'super_admin')->first();
        $superAdminPerms = Permission::whereIn('name', [
            'create_departments',
            'create_users',
            'manage_user_roles',
            'manage_role_permissions',
            'view_audit_logs',
            'view_anticheat_logs',
            'escalate_anticheat_alerts',
            'restore_deleted_data',
            'schedule_exam_session',
            'adjust_session_timing',
            'manage_enrollment_exceptions',
        ])
            ->pluck('id')
            ->toArray();
        $superAdmin->permissions()->sync($superAdminPerms);

        // ===== DEVELOPER - full access, dev/testing only =====
        $developer = Role::where('name', 'developer')->first();
        $developer->permissions()->sync(Permission::pluck('id')->toArray());

        // ===== ADMIN - Exam proctoring + user management =====
        $admin = Role::where('name', 'admin')->first();
        $adminPerms = Permission::whereIn('name', [
            'schedule_exam_session',
            'adjust_session_timing',
            'create_users',
            'manage_user_roles',
            'view_audit_logs',
            'view_anticheat_logs',
            'escalate_anticheat_alerts',
            'manage_enrollment_exceptions',
        ])
            ->pluck('id')
            ->toArray();
        $admin->permissions()->sync($adminPerms);

        // ===== DEPT HEAD - Approvals + Department oversight =====
        $deptHead = Role::where('name', 'dept_head')->first();
        $deptHeadPerms = Permission::whereIn('name', [
            'approve_question_group',
            'view_question_bank',
            'view_question_groups',
            'view_course_analytics',
            'view_student_analytics',
        ])->pluck('id')->toArray();
        $deptHead->permissions()->sync($deptHeadPerms);

        // ===== LEAD INSTRUCTOR - Question bank + package + exam building =====
        $leadInstructor = Role::where('name', 'lead_instructor')->first();
        $leadInstructorPerms = Permission::whereIn('name', [
            'upload_question_bank',
            'view_question_bank',
            'edit_question_import',
            'confirm_question_import',
            'flag_questions',
            'create_questions',
            'edit_questions',
            'manage_tags',
            'create_question_group',
            'edit_question_group',
            'view_question_groups',
            'submit_group_for_approval',
            'create_exam',
        ])->pluck('id')->toArray();
        $leadInstructor->permissions()->sync($leadInstructorPerms);

        // ===== INSTRUCTOR - Grading + Result release =====
        $instructor = Role::where('name', 'instructor')->first();
        $instructorPerms = Permission::whereIn('name', [
            'view_question_bank',
            'flag_questions',
            'resolve_flags',
            'view_grading_queue',
            'grade_essay_questions',
            'release_results',
            'revise_grades_within_window',
            'view_per_question_metrics',
        ])->pluck('id')->toArray();
        $instructor->permissions()->sync($instructorPerms);

        // ===== STUDENT - Exam taking only =====
        $student = Role::where('name', 'student')->first();
        $studentPerms = Permission::whereIn('name', [
            'view_eligible_exams',
            'start_exam_attempt',
            'submit_exam_answers',
            'finalize_exam_attempt',
            'log_anticheat_events',
        ])
            ->pluck('id')
            ->toArray();
        $student->permissions()->sync($studentPerms);
    }
}
