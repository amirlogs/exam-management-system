<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Database\Seeder;

class InstructorSeeder extends Seeder
{
    public function run(): void
    {
        $software = Department::where(
            'name',
            'Software Engineering'
        )->firstOrFail();

        $computerScience = Department::where(
            'name',
            'Computer Science'
        )->firstOrFail();

        $electrical = Department::where(
            'name',
            'Electrical Engineering'
        )->firstOrFail();

        $assignments = [
            [
                'email' => 'instructor.se1@uems.test',
                'department_id' => $software->id,
                'employee_number' => 'SE-INS-001',
                'academic_rank' => 'Assistant Professor',
            ],
            [
                'email' => 'instructor.cs1@uems.test',
                'department_id' => $computerScience->id,
                'employee_number' => 'CS-INS-001',
                'academic_rank' => 'Assistant Professor',
            ],
            [
                'email' => 'instructor.ee1@uems.test',
                'department_id' => $electrical->id,
                'employee_number' => 'EE-INS-001',
                'academic_rank' => 'Lecturer',
            ],
            [
                'email' => 'instructor.se2@uems.test',
                'department_id' => $software->id,
                'employee_number' => 'SE-INS-002',
                'academic_rank' => 'Lecturer',
            ],
            [
                'email' => 'instructor.cs2@uems.test',
                'department_id' => $computerScience->id,
                'employee_number' => 'CS-INS-002',
                'academic_rank' => 'Lecturer',
            ],
            [
                'email' => 'multirole@uems.test',
                'department_id' => $software->id,
                'employee_number' => 'SE-INS-003',
                'academic_rank' => 'Lecturer',
            ],
        ];

        foreach ($assignments as $assignment) {
            $user = User::where(
                'email',
                $assignment['email']
            )->firstOrFail();

            Instructor::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'department_id' => $assignment['department_id'],
                    'employee_number' => $assignment['employee_number'],
                    'academic_rank' => $assignment['academic_rank'],
                    'status' => 'active',
                ]
            );
        }
    }
}
