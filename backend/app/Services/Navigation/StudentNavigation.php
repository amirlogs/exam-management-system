<?php

namespace App\Services\Navigation;

class StudentNavigation
{
    public static function items(): array
    {
        return [
            [
                'label' => 'Dashboard',
                'to' => '/student/dashboard',
                'icon' => 'LayoutGrid',
                'permissions' => [],
            ],
            [
                'label' => 'My Exams',
                'to' => '/student/exams',
                'icon' => 'FileText',
                'permissions' => [
                    'student.exam.take',
                ],
                'permission_mode' => 'any',
            ],
            [
                'label' => 'My Results',
                'to' => '/student/results',
                'icon' => 'Award',
                'permissions' => [
                    'result.view_own',
                ],
                'permission_mode' => 'any',
            ],
        ];
    }
}
