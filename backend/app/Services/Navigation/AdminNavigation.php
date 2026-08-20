<?php

namespace App\Services\Navigation;

class AdminNavigation
{
    public static function items(): array
    {
        return [
            [
                'key' => 'dashboard',
                'label' => 'Dashboard',
                'route' => '/admin/dashboard',
                'icon' => 'LayoutGrid',
                'permissions' => [],
            ],

            [
                'key' => 'universities',
                'label' => 'Universities',
                'route' => '/admin/universities',
                'icon' => 'Landmark',
                'permissions' => [
                    'university.view',
                    'university.view_archived',
                ],
                'permission_mode' => 'any',
            ],

            [
                'key' => 'colleges',
                'label' => 'Colleges',
                'route' => '/admin/colleges',
                'icon' => 'Building2',
                'permissions' => [
                    'college.view',
                    'college.view_archived',
                ],
                'permission_mode' => 'any',
            ],

            [
                'key' => 'departments',
                'label' => 'Departments',
                'route' => '/admin/departments',
                'icon' => 'Building2',
                'permissions' => [
                    'department.view',
                    'department.view_archived',
                ],
                'permission_mode' => 'any',
            ],

            [
                'key' => 'programs',
                'label' => 'Programs',
                'route' => '/admin/programs',
                'icon' => 'GraduationCap',
                'permissions' => [
                    'program.view',
                    'program.view_archived',
                ],
                'permission_mode' => 'any',
            ],

            [
                'key' => 'courses',
                'label' => 'Courses',
                'route' => '/admin/courses',
                'icon' => 'BookOpen',
                'permissions' => [
                    'course.view',
                    'course.view_archived',
                ],
                'permission_mode' => 'any',
            ],

            [
                'key' => 'curriculum',
                'label' => 'Curriculum',
                'route' => '/admin/curriculum',
                'icon' => 'ListTree',
                'permissions' => [
                    'curriculum.view',
                ],
                'permission_mode' => 'any',
            ],

            [
                'key' => 'semesters',
                'label' => 'Semesters',
                'route' => '/admin/semesters',
                'icon' => 'CalendarDays',
                'permissions' => [
                    'semester.view',
                    'semester.view_archived',
                ],
                'permission_mode' => 'any',
            ],

            [
                'key' => 'sections',
                'label' => 'Sections',
                'route' => '/admin/sections',
                'icon' => 'Users2',
                'permissions' => [
                    'section.view',
                    'section.view_archived',
                ],
                'permission_mode' => 'any',
            ],

            [
                'key' => 'imports',
                'label' => 'Data Import',
                'route' => '/admin/imports',
                'icon' => 'Upload',
                'permissions' => [
                    'import.view',
                ],
                'permission_mode' => 'any',
            ],

            [
                'key' => 'course-offerings',
                'label' => 'Course Offerings',
                'route' => '/admin/course-offerings',
                'icon' => 'ClipboardList',
                'permissions' => [
                    'course_offering.view',
                ],
                'permission_mode' => 'any',
            ],

            [
                'key' => 'users',
                'label' => 'Users',
                'route' => '/admin/users',
                'icon' => 'Users2',
                'permissions' => [
                    'user.view',
                ],
                'permission_mode' => 'any',
            ],

            [
                'key' => 'roles',
                'label' => 'Roles & Permissions',
                'route' => '/admin/roles',
                'icon' => 'ShieldCheck',
                'permissions' => [
                    'role.view',
                    'permission.view',
                ],
                'permission_mode' => 'any',
            ],

            [
                'key' => 'results',
                'label' => 'Results',
                'route' => '/admin/results',
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
