<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        $roles = [
            'super_admin' => 'Platform-level management (global scope)',
            'university_admin' => 'Manages the university instance',
            'college_admin' => 'Manages activities inside a college',
            'dept_head' => 'Academic authority of a department',
            'exam_admin' => 'Manages examination operations and scheduling',
            'lead_instructor' => 'Coordinates assigned course offerings and official exams',
            'instructor' => 'Teaches assigned course offerings and grades students',
            'student' => 'Student access for examinations and personal academic results',
        ];

        foreach ($roles as $name => $description) {
            DB::table('roles')->updateOrInsert(
                ['name' => $name],
                [
                    'description' => $description,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Permissions
        |--------------------------------------------------------------------------
        */

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
            ['section.import', 'section'],

            // Instructor
            ['instructor.view', 'instructor'],
            ['instructor.create', 'instructor'],
            ['instructor.update', 'instructor'],
            ['instructor.archive', 'instructor'],
            ['instructor.restore', 'instructor'],
            ['instructor.import', 'instructor'],

            // Student
            ['student.import', 'student'],

            // Course Offering
            ['course_offering.create', 'course_offering'],
            ['course_offering.view', 'course_offering'],
            ['course_offering.update', 'course_offering'],
            ['course_offering.approve', 'course_offering'],
            ['course_offering.reject', 'course_offering'],
            ['course_offering.cancel', 'course_offering'],
            ['course_offering.archive', 'course_offering'],
            ['course_offering.restore', 'course_offering'],
            ['course_offering.assign_instructor', 'course_offering'],
            ['course_offering.change_instructor', 'course_offering'],
            ['course_offering.remove_instructor', 'course_offering'],

            // Enrollment
            ['enrollment.create', 'enrollment'],
            ['enrollment.view', 'enrollment'],
            ['enrollment.update', 'enrollment'],

            // User
            ['user.create', 'user'],
            ['user.view', 'user'],
            ['user.update', 'user'],
            ['user.disable', 'user'],
            ['user.activate', 'user'],
            ['user.reset_password', 'user'],
            ['user.import', 'user'],

            // Role
            ['role.create', 'role'],
            ['role.view', 'role'],
            ['role.update', 'role'],
            ['role.archive', 'role'],
            ['role.restore', 'role'],
            ['role.assign', 'role'],
            ['role.remove', 'role'],

            // Permission
            ['permission.view', 'permission'],
            ['permission.assign', 'permission'],
            ['permission.remove', 'permission'],

            // Import
            ['import.view', 'import'],

            // Question
            ['question.create', 'question'],
            ['question.view', 'question'],
            ['question.update', 'question'],
            ['question.archive', 'question'],
            ['question.restore', 'question'],
            ['question.import', 'question'],
            ['question.export', 'question'],
            ['question.flag', 'question'],

            // Exams
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

            // Student Exam
            ['student.exam.take', 'student_exam'],

            // Grading
            ['grade.autograde', 'grade'],
            ['grade.create', 'grade'],
            ['grade.update', 'grade'],
            ['grade.submit', 'grade'],
            ['grade.verify', 'grade'],
            ['grade.publish', 'grade'],

            // Results
            ['result.view', 'result'],
            ['result.view_own', 'result'],
            ['result.generate', 'result'],
            ['result.publish', 'result'],
            ['result.export', 'result'],
            ['result.print', 'result'],
        ];

        foreach ($permissions as [$name, $category]) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                [
                    'category' => $category,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Shared permissions
        |--------------------------------------------------------------------------
        */

        $commonAdminViews = [
            'university.view',
            'college.view',
            'department.view',
            'program.view',
            'course.view',
            'curriculum.view',
            'semester.view',
            'section.view',
            'instructor.view',
            'course_offering.view',
            'enrollment.view',
            'user.view',
            'role.view',
            'permission.view',
            'import.view',
        ];

        $instructorShared = [
            'course_offering.view',
            'question.create',
            'question.view',
            'question.update',
            'question.archive',
            'question.restore',
            'question.flag',
            'exam.view',
            'grade.autograde',
            'grade.create',
            'grade.update',
            'grade.submit',
        ];

        /*
        |--------------------------------------------------------------------------
        | Role -> Permissions
        |--------------------------------------------------------------------------
        */

        $map = [

            /*
             * Super Admin
             */
            'super_admin' => array_column($permissions, 0),

            /*
             * University Admin
             */
            'university_admin' => array_merge(
                $commonAdminViews,
                [
                    'university.update',
                    'university.archive',
                    'university.restore',

                    'college.create',
                    'college.update',
                    'college.archive',
                    'college.restore',

                    'department.create',
                    'department.update',
                    'department.archive',
                    'department.restore',

                    'program.create',
                    'program.update',
                    'program.archive',
                    'program.restore',

                    'course.create',
                    'course.update',
                    'course.archive',
                    'course.restore',

                    'curriculum.create',
                    'curriculum.update',
                    'curriculum.activate',
                    'curriculum.deactivate',
                    'curriculum.course.add',
                    'curriculum.course.update',
                    'curriculum.course.remove',

                    'semester.create',
                    'semester.update',
                    'semester.open',
                    'semester.close',
                    'semester.archive',
                    'semester.restore',

                    'section.create',
                    'section.update',
                    'section.archive',
                    'section.restore',
                    'section.import',

                    'instructor.view',
                    'instructor.create',
                    'instructor.update',
                    'instructor.import',

                    'student.import',

                    'course_offering.create',
                    'course_offering.update',
                    'course_offering.approve',
                    'course_offering.reject',
                    'course_offering.cancel',
                    'course_offering.archive',
                    'course_offering.restore',
                    'course_offering.assign_instructor',
                    'course_offering.change_instructor',
                    'course_offering.remove_instructor',

                    'enrollment.create',
                    'enrollment.update',

                    'user.create',
                    'user.update',
                    'user.disable',
                    'user.activate',
                    'user.reset_password',
                    'user.import',

                    'role.create',
                    'role.update',
                    'role.archive',
                    'role.restore',
                    'role.assign',
                    'role.remove',

                    'permission.assign',
                    'permission.remove',

                    'question.import',

                    'result.view',
                    'result.generate',
                    'result.publish',
                    'result.export',
                    'result.print',
                ]
            ),

            /*
             * College Admin
             */
            'college_admin' => array_merge(
                $commonAdminViews,
                [
                    'college.update',

                    'department.create',
                    'department.update',
                    'department.archive',
                    'department.restore',

                    'program.create',
                    'program.update',
                    'program.archive',
                    'program.restore',

                    'course.view',

                    'instructor.view',
                    'instructor.import',

                    'student.import',

                    'section.import',

                    'course_offering.view',
                    'course_offering.approve',
                    'course_offering.reject',

                    'enrollment.view',

                    'result.view',
                ]
            ),

            /*
             * Department Head
             */
            'dept_head' => array_merge(
                [
                    'college.view',
                    'department.view',
                    'program.view',
                    'course.view',
                    'curriculum.view',
                    'semester.view',
                    'section.view',
                    'instructor.view',
                    'course_offering.view',
                    'enrollment.view',
                ],
                [
                    'department.update',

                    'program.create',
                    'program.update',

                    'course.create',
                    'course.update',

                    'curriculum.create',
                    'curriculum.update',
                    'curriculum.course.add',
                    'curriculum.course.update',
                    'curriculum.course.remove',

                    'section.create',
                    'section.update',
                    'section.archive',
                    'section.import',

                    'instructor.view',
                    'instructor.import',

                    'student.import',

                    'course_offering.create',
                    'course_offering.update',
                    'course_offering.approve',
                    'course_offering.reject',
                    'course_offering.assign_instructor',
                    'course_offering.change_instructor',
                    'course_offering.remove_instructor',

                    'exam.approve',
                    'exam.reject',

                    'grade.verify',
                    'grade.publish',

                    'result.view',
                ]
            ),

            /*
             * Exam Admin
             */
            'exam_admin' => [
                'course_offering.view',
                'exam.view',
                'exam.schedule',
                'exam.schedule.update',
                'exam.extend',
                'exam.activate',
                'exam.publish',
                'exam.end',
                'exam.cancel',
                'exam.archive',
                'result.view',
            ],

            /*
             * Lead Instructor
             */
            'lead_instructor' => array_merge(
                $instructorShared,
                [
                    'question.import',
                    'exam.create',
                    'exam.update',
                    'exam.submit',
                    'exam.revert',
                    'exam.archive',
                ]
            ),

            /*
             * Instructor
             */
            'instructor' => array_merge(
                $instructorShared,
                [
                    'question.import',
                ]
            ),

            /*
             * Student
             */
            'student' => [
                'student.exam.take',
                'result.view_own',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Persist role permissions
        |--------------------------------------------------------------------------
        */

        foreach ($map as $roleName => $permissionNames) {
            $roleId = DB::table('roles')
                ->where('name', $roleName)
                ->value('id');

            if (! $roleId) {
                continue;
            }

            $permissionIds = DB::table('permissions')
                ->whereIn('name', array_unique($permissionNames))
                ->pluck('id');

            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ],
                    [
                        'created_at' => now(),
                    ]
                );
            }
        }
    }
}
