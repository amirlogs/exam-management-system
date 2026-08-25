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
                'label' => 'My Courses',
                'to' => '/instructor/courses',
                'icon' => 'BookOpen',
                'permissions' => ['course_offering.view'],
                'permission_mode' => 'any',
            ],
            [
                'label' => 'Question Bank',
                'to' => '/instructor/question-bank',
                'icon' => 'HelpCircle',
                'permissions' => ['question.create', 'question.view'],
                'permission_mode' => 'any',
            ],
            [
                'label' => 'Exams',
                'to' => '/instructor/exams',
                'icon' => 'FileText',
                'permissions' => ['exam.create', 'exam.update'],
                'permission_mode' => 'any',
            ],
            [
                'label' => 'Grading',
                'to' => '/instructor/grading',
                'icon' => 'ClipboardCheck',
                'permissions' => ['grade.create'],
                'permission_mode' => 'any',
            ],
            [
                'label' => 'Flags',
                'to' => '/instructor/flags',
                'icon' => 'Flag',
                'permissions' => [],
            ],
        ];
    }
}
