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
            'college_admin' => 'Manages activities inside a college (university_id + college_id scope)',
            'dept_head' => 'Academic authority of a department (university_id + college_id + department_id scope)',
            'exam_admin' => 'Operational exam scheduling and live execution',
            'lead_instructor' => 'Coordinates a course, prepares official exams, and manages grades',
            'instructor' => 'Teaches an assigned course offering and handles student grading',
            'student' => 'Student access (taking exams and viewing personal performance)',
        ];

        foreach ($roles as $name => $description) {
            DB::table('roles')->updateOrInsert(
                ['name' => $name],
                ['description' => $description, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // Complete permission matrix derived directly from api.php routes middleware
        $permissions = [
            // University
            ['university.create', 'university'],
            ['university.view', 'university'],
            ['university.update', 'university'],
            ['university.archive', 'university'],
            ['university.restore', 'university'],

            // College
            ['college.create', 'college'],
            ['college.view', 'college'],
            ['college.update', 'college'],
            ['college.archive', 'college'],
            ['college.restore', 'college'],

            // Department
            ['department.create', 'department'],
            ['department.view', 'department'],
            ['department.update', 'department'],
            ['department.archive', 'department'],
            ['department.restore', 'department'],

            // Program
            ['program.create', 'program'],
            ['program.view', 'program'],
            ['program.update', 'program'],
            ['program.archive', 'program'],
            ['program.restore', 'program'],

            // Course
            ['course.create', 'course'],
            ['course.view', 'course'],
            ['course.update', 'course'],
            ['course.archive', 'course'],
            ['course.restore', 'course'],

            // Role & Permission Management
            ['role.create', 'role'],
            ['role.view', 'role'],
            ['role.update', 'role'],
            ['role.archive', 'role'],
            ['role.restore', 'role'],
            ['role.assign', 'role'],
            ['role.remove', 'role'],
            ['permission.view', 'permission'],
            ['permission.assign', 'permission'],
            ['permission.remove', 'permission'],

            // User Management
            ['user.create', 'user'],
            ['user.view', 'user'],
            ['user.update', 'user'],
            ['user.disable', 'user'],

            // Curriculum
            ['curriculum.create', 'curriculum'],
            ['curriculum.view', 'curriculum'],
            ['curriculum.update', 'curriculum'],
            ['curriculum.activate', 'curriculum'],
            ['curriculum.deactivate', 'curriculum'],
            ['curriculum.course.add', 'curriculum'],
            ['curriculum.course.update', 'curriculum'],
            ['curriculum.course.remove', 'curriculum'],

            // Semester
            ['semester.create', 'semester'],
            ['semester.view', 'semester'],
            ['semester.update', 'semester'],
            ['semester.open', 'semester'],
            ['semester.close', 'semester'],
            ['semester.archive', 'semester'],
            ['semester.restore', 'semester'],

            // Section
            ['section.create', 'section'],
            ['section.view', 'section'],
            ['section.update', 'section'],
            ['section.archive', 'section'],
            ['section.restore', 'section'],

            // Course Offering & Instructor/Section Assignments
            ['course_offering.create', 'course_offering'],
            ['course_offering.view', 'course_offering'],
            ['course_offering.update', 'course_offering'],
            ['course_offering.approve', 'course_offering'],
            ['course_offering.reject', 'course_offering'],
            ['course_offering.cancel', 'course_offering'],
            ['course_offering.archive', 'course_offering'],
            ['course_offering.restore', 'course_offering'],
            ['course_offering.section.assign', 'course_offering'],
            ['course_offering.section.remove', 'course_offering'],
            ['course_offering.section.enroll', 'course_offering'],
            ['course_offering.instructor.assign', 'course_offering'],
            ['course_offering.instructor.remove', 'course_offering'],

            // Enrollment
            ['enrollment.create', 'enrollment'],
            ['enrollment.view', 'enrollment'],
            ['enrollment.update', 'enrollment'],

            // Question Bank & Flagging
            ['question.create', 'question'],
            ['question.view', 'question'],
            ['question.update', 'question'],
            ['question.archive', 'question'],
            ['question.restore', 'question'],
            ['question.flag', 'question'],

            // Exam Administration (Faculty / Admin side)
            ['exam.create', 'exam'],
            ['exam.view', 'exam'],
            ['exam.update', 'exam'],
            ['exam.submit', 'exam'],
            ['exam.revert', 'exam'],
            ['exam.approve', 'exam'],
            ['exam.reject', 'exam'],
            ['exam.schedule', 'exam'],
            ['exam.schedule.update', 'exam'],
            ['exam.extend', 'exam'],
            ['exam.activate', 'exam'],
            ['exam.publish', 'exam'],
            ['exam.end', 'exam'],
            ['exam.cancel', 'exam'],
            ['exam.archive', 'exam'],

            // Student Exam Execution
            ['student.exam.take', 'student_exam'],

            // Grading
            ['grade.autograde', 'grade'],
            ['grade.update', 'grade'],
            ['grade.submit', 'grade'],
            ['grade.verify', 'grade'],
            ['grade.publish', 'grade'],

            // Results
            ['result.view', 'result'],
            ['result.view_own', 'result'],
        ];

        foreach ($permissions as [$name, $category]) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                ['category' => $category, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // Shared view permissions for administrative hierarchy
        $commonAdminViews = [
            'university.view', 'college.view', 'department.view', 'program.view',
            'course.view', 'semester.view', 'section.view', 'curriculum.view',
            'course_offering.view', 'user.view', 'role.view', 'permission.view',
        ];

        // Shared permissions between instructors and lead instructors
        $instructorShared = [
            'question.create', 'question.view', 'question.update', 'question.archive',
            'question.restore', 'question.flag', 'grade.autograde', 'grade.update',
            'grade.submit', 'course_offering.view', 'exam.view', 'result.view',
        ];

        $map = [
            // Super Admin gets all permissions dynamically
            'super_admin' => array_column($permissions, 0),

            // University Admin (Full administrative capabilities across university entities)
            'university_admin' => array_merge($commonAdminViews, [
                'university.update',
                'college.create', 'college.update', 'college.archive', 'college.restore',
                'department.create', 'department.update', 'department.archive', 'department.restore',
                'program.create', 'program.update', 'program.archive', 'program.restore',
                'course.create', 'course.update', 'course.archive', 'course.restore',
                'role.create', 'role.update', 'role.archive', 'role.restore', 'role.assign', 'role.remove',
                'permission.assign', 'permission.remove',
                'user.create', 'user.update', 'user.disable',
                'curriculum.create', 'curriculum.update', 'curriculum.activate', 'curriculum.deactivate',
                'curriculum.course.add', 'curriculum.course.update', 'curriculum.course.remove',
                'semester.create', 'semester.update', 'semester.open', 'semester.close', 'semester.archive', 'semester.restore',
                'section.create', 'section.update', 'section.archive', 'section.restore',
                'course_offering.create', 'course_offering.update', 'course_offering.cancel', 'course_offering.archive', 'course_offering.restore',
                'course_offering.section.assign', 'course_offering.section.remove', 'course_offering.section.enroll',
                'course_offering.instructor.assign', 'course_offering.instructor.remove',
                'enrollment.create', 'enrollment.view', 'enrollment.update',
                'result.view',
            ]),

            // College Admin
            'college_admin' => array_merge($commonAdminViews, [
                'college.update',
                'department.create', 'department.update', 'department.archive', 'department.restore',
                'program.create', 'program.update', 'program.archive',
                'user.create', 'user.update', 'user.disable',
                'course_offering.approve', 'course_offering.reject',
                'enrollment.view', 'result.view',
            ]),

            // Department Head
            'dept_head' => array_merge($commonAdminViews, [
                'department.update',
                'program.create', 'program.update',
                'course.create', 'course.update',
                'course_offering.create', 'course_offering.update', 'course_offering.approve', 'course_offering.reject',
                'course_offering.section.assign', 'course_offering.section.remove',
                'course_offering.instructor.assign', 'course_offering.instructor.remove',
                'section.create', 'section.update', 'section.archive',
                'exam.approve', 'exam.reject', 'exam.publish',
                'grade.verify', 'grade.publish',
                'result.view',
            ]),

            // Exam Admin
            'exam_admin' => array_merge($commonAdminViews, [
                'exam.view', 'exam.schedule', 'exam.schedule.update', 'exam.extend',
                'exam.activate', 'exam.publish', 'exam.end', 'exam.cancel',
                'result.view',
            ]),

            // Lead Instructor
            'lead_instructor' => array_merge($instructorShared, [
                'exam.create', 'exam.update', 'exam.submit', 'exam.revert',
                'exam.archive', 'course_offering.section.assign',
            ]),

            // Instructor
            'instructor' => $instructorShared,

            // Student (Isolated execution scope)
            'student' => [
                'student.exam.take',
                'result.view_own',
            ],
        ];

        foreach ($map as $roleName => $permNames) {
            $roleId = DB::table('roles')->where('name', $roleName)->value('id');

            if (! $roleId) {
                continue;
            }

            $permIds = DB::table('permissions')->whereIn('name', array_unique($permNames))->pluck('id');

            foreach ($permIds as $permId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permId],
                    ['created_at' => now()]
                );
            }
        }
    }
}
