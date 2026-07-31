<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'super_admin' => 'Platform-level management (global scope, no scope columns set)',
            'university_admin' => 'Manages the university instance (university_id scope)',
            'college_admin' => 'Manages activities inside a college (university_id + college_id scope) — optional',
            'dept_head' => 'Academic authority of a department (university_id + college_id + department_id scope)',
            'exam_admin' => 'Operational exam scheduling — scope decided at install: university_id OR department_id — optional',
            'lead_instructor' => 'Coordinates a course and prepares official exams (assignment via course_instructors)',
            'instructor' => 'Teaches an assigned course offering (assignment via course_instructors)',
            'student' => 'Student access (own scope only)',
        ];

        foreach ($roles as $name => $description) {
            DB::table('roles')->updateOrInsert(
                ['name' => $name],
                ['description' => $description, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        $permissions = [
            // University
            ['university.create', 'university'], ['university.update', 'university'], ['university.archive', 'university'],
            // Academic Calendar
            ['academic_calendar.create', 'academic_calendar'], ['academic_calendar.update', 'academic_calendar'], ['academic_calendar.archive', 'academic_calendar'],
            // Grading Config
            ['grading_system.create', 'grading_system'], ['grading_system.update', 'grading_system'], ['grading_system.archive', 'grading_system'],
            // College
            ['college.create', 'college'], ['college.update', 'college'], ['college.archive', 'college'],
            // Department
            ['department.create', 'department'], ['department.update', 'department'], ['department.archive', 'department'],
            // Program
            ['program.create', 'program'], ['program.update', 'program'], ['program.archive', 'program'],
            // Course
            ['course.create', 'course'], ['course.update', 'course'], ['course.archive', 'course'],
            // Curriculum
            ['curriculum.create', 'curriculum'], ['curriculum.update', 'curriculum'], ['curriculum.archive', 'curriculum'],
            ['curriculum_version.create', 'curriculum'], ['curriculum_version.update', 'curriculum'],
            ['curriculum_version.activate', 'curriculum'], ['curriculum_version.archive', 'curriculum'],
            ['curriculum_course.add', 'curriculum'], ['curriculum_course.remove', 'curriculum'], ['curriculum_course.update', 'curriculum'],
            // Semester
            ['semester.create', 'semester'], ['semester.update', 'semester'], ['semester.open', 'semester'],
            ['semester.close', 'semester'], ['semester.archive', 'semester'],
            // Import
            ['student.import', 'import'], ['instructor.import', 'import'], ['section.import', 'import'], ['class.import', 'import'],
            // Course Offering (no .review — no defined workflow for it)
            ['course_offering.create', 'course_offering'], ['course_offering.update', 'course_offering'],
            ['course_offering.archive', 'course_offering'], ['course_offering.approve', 'course_offering'],
            ['course_offering.reject', 'course_offering'], ['course_offering.assign_instructor', 'course_offering'],
            ['course_offering.change_instructor', 'course_offering'], ['course_offering.cancel', 'course_offering'],
            // Question Bank
            ['question.create', 'question'], ['question.update', 'question'], ['question.archive', 'question'],
            ['question.restore', 'question'], ['question.import', 'question'], ['question.export', 'question'],
            ['question.approve', 'question'], ['question.reject', 'question'],
            // Exam
            ['exam.create', 'exam'], ['exam.update', 'exam'], ['exam.archive', 'exam'],
            ['exam.submit_approval', 'exam'], ['exam.approve', 'exam'], ['exam.reject', 'exam'],
            ['exam.schedule', 'exam'], ['exam.update_schedule', 'exam'], ['exam.extend_time', 'exam'],
            ['exam.close', 'exam'], ['exam.cancel', 'exam'],
            // Grading
            ['grade.auto_grade', 'grade'], ['grade.create', 'grade'], ['grade.update', 'grade'],
            ['grade.regrade', 'grade'], ['grade.submit_verification', 'grade'], ['grade.verify', 'grade'],
            ['grade.publish', 'grade'], ['grade.unpublish', 'grade'],
            // Results (view vs view_own are different security meanings)
            ['result.view', 'result'], ['result.view_own', 'result'], ['result.generate', 'result'],
            ['result.export', 'result'], ['result.print', 'result'], ['result.publish', 'result'],
            // Users
            ['user.create', 'user'], ['user.update', 'user'], ['user.disable', 'user'],
            ['user.activate', 'user'], ['user.reset_password', 'user'],
            ['user.view', 'user'],
            // Roles
            ['role.create', 'role'], ['role.update', 'role'], ['role.archive', 'role'],
            ['role.assign', 'role'], ['role.remove', 'role'],
            // Permissions
            ['permission.create', 'permission'], ['permission.update', 'permission'],
            ['permission.archive', 'permission'], ['permission.assign', 'permission'],
            // Notifications
            ['notification.create', 'notification'], ['notification.send', 'notification'], ['notification.manage', 'notification'],
            // Reports
            ['report.generate', 'report'], ['report.export', 'report'], ['report.print', 'report'],
            // Audit
            ['audit.view', 'audit'], ['audit.export', 'audit'], ['audit.filter', 'audit'],
        ];

        foreach ($permissions as [$name, $category]) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                ['category' => $category, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        $map = [
            'super_admin' => [
                'university.create', 'university.update', 'university.archive',
                'role.create', 'role.update', 'role.archive', 'role.assign', 'role.remove',
            ],
            'university_admin' => [
                'university.update',
                'college.create', 'college.update', 'college.archive',
                'department.create', 'department.update', 'department.archive',
                'program.create', 'program.update', 'program.archive',
                'course.create', 'course.update', 'course.archive',
                'user.create', 'user.update', 'user.disable', 'user.activate',
                'student.import', 'instructor.import', 'section.import', 'class.import',
                'academic_calendar.create', 'academic_calendar.update', 'academic_calendar.archive',
                'grading_system.create', 'grading_system.update', 'grading_system.archive',
                'semester.create', 'semester.update', 'semester.open', 'semester.close', 'semester.archive',
                'curriculum.create', 'curriculum.update', 'curriculum.archive', 'user.view',
            ],
            // college_admin, dept_head, exam_admin, lead_instructor, instructor, student — unchanged
            'college_admin' => [
                'college.update', 'department.create', 'department.update', 'course_offering.approve',
            ],
            'dept_head' => [
                'department.update', 'course_offering.approve', 'course_offering.assign_instructor',
                'exam.approve', 'exam.reject', 'grade.verify', 'result.view',
                'question.approve', 'question.reject',
            ],
            'exam_admin' => [
                'exam.schedule', 'exam.update_schedule', 'exam.extend_time', 'exam.close',
            ],
            'lead_instructor' => [
                'question.create', 'question.update', 'question.import', 'question.export',
                'exam.create', 'exam.update', 'exam.submit_approval', 'grade.create', 'grade.update',
            ],
            'instructor' => [
                'grade.create', 'grade.update',
            ],
            'student' => [
                'result.view_own',
            ],
        ];
        foreach ($map as $roleName => $permNames) {
            $roleId = DB::table('roles')->where('name', $roleName)->value('id');
            $permIds = DB::table('permissions')->whereIn('name', $permNames)->pluck('id');

            foreach ($permIds as $permId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permId],
                    ['created_at' => now()]
                );
            }
        }
    }
}
