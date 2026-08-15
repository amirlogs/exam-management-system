<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'super_admin' => 'Platform-level management (global scope)',
            'university_admin' => 'Manages the university instance (university_id scope)',
            'college_admin' => 'Manages activities inside a college (university_id + college_id scope) — optional',
            'dept_head' => 'Academic authority of a department (university_id + college_id + department_id scope)',
            'exam_admin' => 'Operational exam scheduling — scope decided at install: university_id OR department_id — optional',
            'lead_instructor' => 'Coordinates a course and prepares official exams (assignment via course_instructors)',
            'instructor' => 'Teaches an assigned course offering (assignment via course_instructors)',
            'student' => 'Student access (own scope only)',
        ];

        foreach ($roles as $name => $description) {
            DB::table('roles')->updateOrInsert(['name' => $name], ['description' => $description, 'created_at' => now(), 'updated_at' => now()]);
        }

        $permissions = [
            // University
            ['university.create', 'university'], ['university.update', 'university'], ['university.archive', 'university'],
            // College / Department / Program / Course
            ['college.create', 'college'], ['college.update', 'college'], ['college.archive', 'college'],
            ['department.create', 'department'], ['department.update', 'department'], ['department.archive', 'department'],
            ['program.create', 'program'], ['program.update', 'program'], ['program.archive', 'program'],
            ['course.create', 'course'], ['course.update', 'course'], ['course.archive', 'course'],
            // Curriculum
            ['curriculum.create', 'curriculum'], ['curriculum.update', 'curriculum'], ['curriculum.archive', 'curriculum'],
            ['curriculum_version.activate', 'curriculum'],
            ['curriculum_course.add', 'curriculum'], ['curriculum_course.remove', 'curriculum'], ['curriculum_course.update', 'curriculum'],
            // Semester
            ['semester.create', 'semester'], ['semester.update', 'semester'], ['semester.open', 'semester'],
            ['semester.close', 'semester'], ['semester.archive', 'semester'],
            // Import
            ['student.import', 'import'], ['instructor.import', 'import'], ['section.import', 'import'], ['class.import', 'import'],
            // Course Offering
            ['course_offering.create', 'course_offering'], ['course_offering.update', 'course_offering'],
            ['course_offering.archive', 'course_offering'], ['course_offering.approve', 'course_offering'],
            ['course_offering.reject', 'course_offering'], ['course_offering.assign_instructor', 'course_offering'],
            ['course_offering.change_instructor', 'course_offering'], ['course_offering.cancel', 'course_offering'],
            // Question Bank — no approve/reject anymore (approval moved to exam-level)
            ['question.create', 'question'], ['question.update', 'question'], ['question.archive', 'question'],
            ['question.restore', 'question'], ['question.import', 'question'], ['question.export', 'question'],
            // Exam
            ['exam.create', 'exam'], ['exam.update', 'exam'], ['exam.archive', 'exam'],
            ['exam.submit_approval', 'exam'], ['exam.approve', 'exam'], ['exam.reject', 'exam'],
            ['exam.schedule', 'exam'], ['exam.update_schedule', 'exam'], ['exam.extend_time', 'exam'], ['exam.publish', 'exam'],
            ['exam.close', 'exam'], ['exam.cancel', 'exam'],
            // Grading
            ['grade.auto_grade', 'grade'], ['grade.create', 'grade'], ['grade.update', 'grade'],
            ['grade.regrade', 'grade'], ['grade.submit_verification', 'grade'], ['grade.verify', 'grade'],
            ['grade.publish', 'grade'], ['grade.unpublish', 'grade'],
            // Results
            ['result.view', 'result'], ['result.view_own', 'result'], ['result.generate', 'result'],
            ['result.export', 'result'], ['result.print', 'result'], ['result.publish', 'result'],
            // Users / Roles / Permissions
            ['user.create', 'user'], ['user.update', 'user'], ['user.view', 'user'],
            ['user.disable', 'user'], ['user.activate', 'user'], ['user.reset_password', 'user'],
            ['role.create', 'role'], ['role.update', 'role'], ['role.archive', 'role'],
            ['role.assign', 'role'], ['role.remove', 'role'],
            ['permission.assign', 'permission'], // create/update/archive intentionally NOT here — permissions are seed-only
        ];

        foreach ($permissions as [$name, $category]) {
            DB::table('permissions')->updateOrInsert(['name' => $name], ['category' => $category, 'created_at' => now(), 'updated_at' => now()]);
        }

        $map = [
            // 'super_admin' => [
            //     'university.create', 'university.update', 'university.archive',
            //     'role.create', 'role.update', 'role.archive', 'role.assign', 'role.remove',
            //     'permission.assign',
            // ],
            // super admin all the permmsions
            'super_admin' => [
                'university.create', 'university.update', 'university.archive',
                'role.create', 'role.update', 'role.archive', 'role.assign', 'role.remove',
                'permission.assign',
                'college.create', 'college.update', 'college.archive',
                'department.create', 'department.update', 'department.archive',
                'program.create', 'program.update', 'program.archive',
                'course.create', 'course.update', 'course.archive',
                'user.create', 'user.update', 'user.view', 'user.disable', 'user.activate',
                'student.import', 'instructor.import', 'section.import', 'class.import',
                'semester.create', 'semester.update', 'semester.open', 'semester.close', 'semester.archive',
                'curriculum.create', 'curriculum.update', 'curriculum.archive', 'curriculum_version.activate',
                'curriculum_course.add', 'curriculum_course.remove', 'curriculum_course.update',
            ],
            'university_admin' => [
                'university.update',
                'college.create', 'college.update', 'college.archive',
                'department.create', 'department.update', 'department.archive',
                'program.create', 'program.update', 'program.archive',
                'course.create', 'course.update', 'course.archive',
                'user.create', 'user.update', 'user.view', 'user.disable', 'user.activate',
                'student.import', 'instructor.import', 'section.import', 'class.import',
                'semester.create', 'semester.update', 'semester.open', 'semester.close', 'semester.archive',
                'curriculum.create', 'curriculum.update', 'curriculum.archive', 'curriculum_version.activate',
                'curriculum_course.add', 'curriculum_course.remove', 'curriculum_course.update',
            ],
            'college_admin' => [
                'college.update', 'department.create', 'department.update', 'course_offering.approve',
            ],
            'dept_head' => [
                'department.update', 'course_offering.approve', 'course_offering.reject', 'course_offering.assign_instructor',
                'exam.approve', 'exam.reject', 'grade.verify', 'result.view', 'exam.publish',
            ],
            'exam_admin' => [
                'exam.schedule', 'exam.update_schedule', 'exam.extend_time', 'exam.close', 'exam.publish',
            ],
            'lead_instructor' => [
                'question.create', 'question.update', 'question.archive', 'question.restore',
                'question.import', 'question.export',
                'exam.create', 'exam.update', 'exam.submit_approval',
                'grade.create', 'grade.update',
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
                DB::table('role_permissions')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $permId], ['created_at' => now()]);
            }
        }
    }
}
