<?php

namespace App\Services\Navigation;

class InstructorNavigation
{
    public static function items(): array
    {
        return [
            [
                'label' => 'Dashboard',
                'to' => '/instructor/dashboard',
                'icon' => 'LayoutGrid',
                'permissions' => [],
            ],

            [
                'label' => 'My Teaching',
                'to' => '/instructor/teaching',
                'icon' => 'BookOpen',
                'group' => 'Academics',
                'permissions' => [
                    'course_offering.view',
                ],
                'permission_mode' => 'any',
            ],

            [
                'label' => 'Assessments',
                'icon' => 'FileQuestion',
                'group' => 'Assessments',
                'children' => [
                    [
                        'label' => 'Question Bank',
                        'to' => '/instructor/questions',
                        'icon' => 'LibraryBig',
                        'permissions' => [
                            'question.view',
                        ],
                        'permission_mode' => 'any',
                    ],
                    [
                        'label' => 'Exams',
                        'to' => '/instructor/exams',
                        'icon' => 'FileQuestion',
                        'permissions' => [
                            'exam.view',
                        ],
                        'permission_mode' => 'any',
                    ],
                    [
                        'label' => 'Grading',
                        'to' => '/instructor/grading',
                        'icon' => 'ClipboardCheck',
                        'permissions' => [
                            'grade.create',
                        ],
                        'permission_mode' => 'any',
                    ],
                    [
                        'label' => 'Results',
                        'to' => '/instructor/results',
                        'icon' => 'BarChart3',
                        'permissions' => [
                            'result.view',
                        ],
                        'permission_mode' => 'any',
                    ],
                ],
            ],
        ];
    }
}
