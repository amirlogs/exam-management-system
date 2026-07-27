<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ===== QUESTION BANK PERMISSIONS =====
        Permission::create([
            'name' => 'upload_question_bank',
            'category' => 'question_bank',
            'description' => 'Upload CSV/Excel questions to question bank',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'confirm_question_import',
            'category' => 'question_bank',
            'description' => 'Confirm and commit uploaded questions to bank',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'flag_questions',
            'category' => 'question_bank',
            'description' => 'Flag questions with issues/typos during review',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'view_question_bank',
            'category' => 'question_bank',
            'description' => 'View questions in the bank (read-only, gates nav visibility)',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'edit_question_import',
            'category' => 'question_bank',
            'description' => 'Edit a row in a pending import before confirming',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'resolve_flags',
            'category' => 'question_bank',
            'description' => 'Mark a question flag as resolved',
            'is_system_defined' => true,
        ]);

        // ===== SINGLE QUESTION MANAGEMENT (new category) =====
        Permission::create([
            'name' => 'create_questions',
            'category' => 'questions',
            'description' => 'Create a single question directly (non-batch)',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'edit_questions',
            'category' => 'questions',
            'description' => 'Edit a draft question, or replace/retire a confirmed one',
            'is_system_defined' => true,
        ]);
        Permission::create([
            'name' => 'delete_questions',
            'category' => 'questions',
            'description' => 'Permanently delete a confirmed question (hard delete)',
            'is_system_defined' => true,
        ]);

        // ===== TAGS (new category) =====
        Permission::create([
            'name' => 'manage_tags',
            'category' => 'tags',
            'description' => 'Create and delete course tags for question categorization',
            'is_system_defined' => true,
        ]);

        // ===== QUESTION GROUPS — additions =====
        Permission::create([
            'name' => 'edit_question_group',
            'category' => 'question_groups',
            'description' => 'Add, remove, or reorder questions in a group; update its label',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'view_question_groups',
            'category' => 'question_groups',
            'description' => 'View question groups (read-only, gates nav visibility)',
            'is_system_defined' => true,
        ]);
        // ===== QUESTION GROUP/PACKAGE PERMISSIONS =====
        Permission::create([
            'name' => 'create_question_group',
            'category' => 'question_groups',
            'description' => 'Create named question group/bundle',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'add_questions_to_group',
            'category' => 'question_groups',
            'description' => 'Add/remove questions from group',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'submit_group_for_approval',
            'category' => 'question_groups',
            'description' => 'Submit question group for Dept Head approval',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'approve_question_group',
            'category' => 'question_groups',
            'description' => 'Approve question group (Dept Head)',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'reopen_question_group',
            'category' => 'question_groups',
            'description' => 'Reopen approved group for editing (Dept Head)',
            'is_system_defined' => true,
        ]);

        // ===== EXAM PERMISSIONS =====
        Permission::create([
            'name' => 'create_exam',
            'category' => 'exams',
            'description' => 'Create exam from approved question group',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'schedule_exam_session',
            'category' => 'exams',
            'description' => 'Schedule exam session (date/time/lab/cohort)',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'detect_lab_conflicts',
            'category' => 'exams',
            'description' => 'System checks lab booking conflicts automatically',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'adjust_session_timing',
            'category' => 'exams',
            'description' => 'Extend/adjust exam session timing (Admin only)',
            'is_system_defined' => true,
        ]);

        // ===== STUDENT EXAM-TAKING PERMISSIONS =====
        Permission::create([
            'name' => 'view_eligible_exams',
            'category' => 'exam_taking',
            'description' => 'View exams student is eligible for (curriculum mapping)',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'start_exam_attempt',
            'category' => 'exam_taking',
            'description' => 'Start new exam attempt in session',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'submit_exam_answers',
            'category' => 'exam_taking',
            'description' => 'Submit individual answers during exam',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'finalize_exam_attempt',
            'category' => 'exam_taking',
            'description' => 'Manually submit/finalize exam before time expires',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'log_anticheat_events',
            'category' => 'exam_taking',
            'description' => 'Log tab-switch/focus-loss events (system automatic)',
            'is_system_defined' => true,
        ]);

        // ===== AUTO-GRADING & SUBMISSION =====
        Permission::create([
            'name' => 'auto_grade_mcq',
            'category' => 'grading',
            'description' => 'System auto-grades MCQ/True-False immediately (automatic)',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'auto_submit_expired_attempts',
            'category' => 'grading',
            'description' => 'System auto-submits attempts at scheduled_end (background job)',
            'is_system_defined' => true,
        ]);

        // ===== ESSAY GRADING PERMISSIONS =====
        Permission::create([
            'name' => 'view_grading_queue',
            'category' => 'grading',
            'description' => 'View list of ungraded essays to grade',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'grade_essay_questions',
            'category' => 'grading',
            'description' => 'Score essay/short-answer questions',
            'is_system_defined' => true,
        ]);

        // ===== RESULT RELEASE PERMISSIONS =====
        Permission::create([
            'name' => 'release_results',
            'category' => 'results',
            'description' => 'Release exam results to students (opens edit window)',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'revise_grades_within_window',
            'category' => 'results',
            'description' => 'Revise grades within edit window (default 7 days)',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'revise_grades_after_window',
            'category' => 'results',
            'description' => 'Revise grades after edit window (requires Admin approval)',
            'is_system_defined' => true,
        ]);

        // ===== ANALYTICS PERMISSIONS =====
        Permission::create([
            'name' => 'view_course_analytics',
            'category' => 'analytics',
            'description' => 'View course-level analytics (teaching dept only)',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'view_student_analytics',
            'category' => 'analytics',
            'description' => 'View student-level analytics (home dept only)',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'view_per_question_metrics',
            'category' => 'analytics',
            'description' => 'View difficulty/avg_time per question',
            'is_system_defined' => true,
        ]);

        // ===== ENROLLMENT EXCEPTIONS =====
        Permission::create([
            'name' => 'manage_enrollment_exceptions',
            'category' => 'enrollment',
            'description' => 'Add/exclude students from exams (Admin override)',
            'is_system_defined' => true,
        ]);

        // ===== SYSTEM ADMIN PERMISSIONS =====
        Permission::create([
            'name' => 'create_departments',
            'category' => 'admin',
            'description' => 'Create new departments',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'create_users',
            'category' => 'admin',
            'description' => 'Create new users (import or manual)',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'manage_user_roles',
            'category' => 'admin',
            'description' => 'Assign/revoke roles to users',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'manage_role_permissions',
            'category' => 'admin',
            'description' => 'Define/modify permissions per role',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'view_audit_logs',
            'category' => 'admin',
            'description' => 'View system audit trail (who did what/when)',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'view_anticheat_logs',
            'category' => 'admin',
            'description' => 'View anti-cheat violation logs',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'escalate_anticheat_alerts',
            'category' => 'admin',
            'description' => 'Trigger alerts for repeated violations',
            'is_system_defined' => true,
        ]);

        Permission::create([
            'name' => 'restore_deleted_data',
            'category' => 'admin',
            'description' => 'Restore soft-deleted records',
            'is_system_defined' => true,
        ]);

    }
}
