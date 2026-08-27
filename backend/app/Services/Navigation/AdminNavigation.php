<?php

namespace App\Services\Navigation;

class AdminNavigation
{
    public static function items(): array
    {
        return [
            [
                'label' => 'Dashboard',
                'to' => '/admin/dashboard',
                'icon' => 'LayoutGrid',
                'permissions' => [],
            ],

            [
                'label' => 'Universities',
                'to' => '/admin/universities',
                'icon' => 'Landmark',
                'permissions' => [
                    'university.view',
                    'university.view_archived',
                ],
                'permission_mode' => 'any',
            ],
            [
                'label' => 'Colleges',
                'to' => '/admin/colleges',
                'icon' => 'Building2',
                'permissions' => [
                    'college.view',
                    'college.view_archived',
                ],
                'permission_mode' => 'any',
            ],

            [
                'label' => 'Departments',
                'to' => '/admin/departments',
                'icon' => 'Building2',
                'permissions' => [
                    'department.view',
                    'department.view_archived',
                ],
                'permission_mode' => 'any',
            ],

            [
                'label' => 'Programs',
                'to' => '/admin/programs',
                'icon' => 'GraduationCap',
                'permissions' => [
                    'program.view',
                    'program.view_archived',
                ],
                'permission_mode' => 'any',
            ],

            [
                'label' => 'Courses',
                'to' => '/admin/courses',
                'icon' => 'BookOpen',
                'permissions' => [
                    'course.view',
                    'course.view_archived',
                ],
                'permission_mode' => 'any',
            ],

            [
                'label' => 'Curriculum',
                'to' => '/admin/curriculum',
                'icon' => 'ListTree',
                'permissions' => [
                    'curriculum.view',
                ],
                'permission_mode' => 'any',
            ],

            [
                'label' => 'Semesters',
                'to' => '/admin/semesters',
                'icon' => 'CalendarDays',
                'permissions' => [
                    'semester.view',
                    'semester.view_archived',
                ],
                'permission_mode' => 'any',
            ],

            [
                'label' => 'Sections',
                'to' => '/admin/sections',
                'icon' => 'Users2',
                'permissions' => [
                    'section.view',
                    'section.view_archived',
                ],
                'permission_mode' => 'any',
            ],
            [
                'label' => 'Data Import',
                'to' => '/admin/imports',
                'icon' => 'Upload',
                'permissions' => [
                    'import.view',
                ],
                'permission_mode' => 'any',
            ],
            [
                'label' => 'Course Offerings',
                'to' => '/admin/course-offerings',
                'icon' => 'ClipboardList',
                'permissions' => [
                    'course_offering.view',
                ],
                'permission_mode' => 'any',
            ],

            [
                'label' => 'Users',
                'to' => '/admin/users',
                'icon' => 'Users2',
                'permissions' => [
                    'user.view',
                ],
                'permission_mode' => 'any',
            ],

            [
                'label' => 'Roles & Permissions',
                'to' => '/admin/roles',
                'icon' => 'ShieldCheck',
                'permissions' => [
                    'role.view',
                    'permission.view',
                ],
                'permission_mode' => 'any',
            ],

            [
                'label' => 'Results',
                'to' => '/admin/results',
                'icon' => 'BarChart3',
                'permissions' => [
                    'result.view',
                    'result.view_archived',
                ],
                'permission_mode' => 'any',
            ],
        ];
    }
}
