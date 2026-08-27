<?php

namespace Database\Seeders;

use App\Models\College;
use App\Models\Course;
use App\Models\Department;
use App\Models\Program;
use App\Models\University;
use Illuminate\Database\Seeder;

class AcademicStructureSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | University
        |--------------------------------------------------------------------------
        */

        $university = University::updateOrCreate(
            ['code' => 'ASTU'],
            [
                'name' => 'Adama Science and Technology University',
                'address' => 'Adama, Ethiopia',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Colleges
        |--------------------------------------------------------------------------
        */

        $engineering = College::updateOrCreate(
            [
                'university_id' => $university->id,
                'name' => 'College of Engineering',
            ]
        );

        $appliedSciences = College::updateOrCreate(
            [
                'university_id' => $university->id,
                'name' => 'College of Applied Sciences',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Departments
        |--------------------------------------------------------------------------
        */

        $softwareEngineering = Department::updateOrCreate(
            [
                'college_id' => $engineering->id,
                'name' => 'Software Engineering',
            ],
            [
                'type' => 'degree_granting',
            ]
        );

        $computerScience = Department::updateOrCreate(
            [
                'college_id' => $engineering->id,
                'name' => 'Computer Science',
            ],
            [
                'type' => 'degree_granting',
            ]
        );

        $electricalEngineering = Department::updateOrCreate(
            [
                'college_id' => $engineering->id,
                'name' => 'Electrical Engineering',
            ],
            [
                'type' => 'degree_granting',
            ]
        );

        $mathematics = Department::updateOrCreate(
            [
                'college_id' => $engineering->id,
                'name' => 'Mathematics',
            ],
            [
                'type' => 'service_only',
            ]
        );

        $chemistry = Department::updateOrCreate(
            [
                'college_id' => $appliedSciences->id,
                'name' => 'Applied Chemistry',
            ],
            [
                'type' => 'degree_granting',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Programs
        |--------------------------------------------------------------------------
        */

        Program::updateOrCreate(
            ['code' => 'SE'],
            [
                'department_id' => $softwareEngineering->id,
                'name' => 'Software Engineering',
                'duration_years' => 5,
            ]
        );

        Program::updateOrCreate(
            ['code' => 'CS'],
            [
                'department_id' => $computerScience->id,
                'name' => 'Computer Science',
                'duration_years' => 4,
            ]
        );

        Program::updateOrCreate(
            ['code' => 'EE'],
            [
                'department_id' => $electricalEngineering->id,
                'name' => 'Electrical Engineering',
                'duration_years' => 5,
            ]
        );

        Program::updateOrCreate(
            ['code' => 'CHEM'],
            [
                'department_id' => $chemistry->id,
                'name' => 'Applied Chemistry',
                'duration_years' => 4,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Courses
        |--------------------------------------------------------------------------
        |
        | Courses belong to their owning department.
        | They may later be included in multiple program curricula.
        |
        */

        $courses = [
            [
                'code' => 'MATH201',
                'department_id' => $mathematics->id,
                'name' => 'Discrete Mathematics',
                'credit_hours' => 3,
            ],
            [
                'code' => 'MATH101',
                'department_id' => $mathematics->id,
                'name' => 'Calculus I',
                'credit_hours' => 4,
            ],
            [
                'code' => 'SE201',
                'department_id' => $softwareEngineering->id,
                'name' => 'Object-Oriented Programming',
                'credit_hours' => 4,
            ],
            [
                'code' => 'SE301',
                'department_id' => $softwareEngineering->id,
                'name' => 'Database Systems',
                'credit_hours' => 4,
            ],
            [
                'code' => 'CS201',
                'department_id' => $computerScience->id,
                'name' => 'Data Structures',
                'credit_hours' => 4,
            ],
            [
                'code' => 'CS301',
                'department_id' => $computerScience->id,
                'name' => 'Operating Systems',
                'credit_hours' => 4,
            ],
            [
                'code' => 'EE201',
                'department_id' => $electricalEngineering->id,
                'name' => 'Circuit Analysis',
                'credit_hours' => 3,
            ],
            [
                'code' => 'EE301',
                'department_id' => $electricalEngineering->id,
                'name' => 'Digital Electronics',
                'credit_hours' => 3,
            ],
            [
                'code' => 'CHEM101',
                'department_id' => $chemistry->id,
                'name' => 'General Chemistry',
                'credit_hours' => 3,
            ],
            [
                'code' => 'CHEM201',
                'department_id' => $chemistry->id,
                'name' => 'Organic Chemistry',
                'credit_hours' => 3,
            ],
        ];

        foreach ($courses as $course) {
            Course::updateOrCreate(
                ['code' => $course['code']],
                [
                    'department_id' => $course['department_id'],
                    'name' => $course['name'],
                    'credit_hours' => $course['credit_hours'],
                ]
            );
        }
    }
}
