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
        // ---------------- University ----------------
        $astu = University::updateOrCreate(
            ['code' => 'ASTU'],
            ['name' => 'Addis Science and Technology University', 'address' => 'Addis Ababa, Ethiopia']
        );

        // ---------------- Colleges ----------------
        $engineering = College::updateOrCreate(
            ['university_id' => $astu->id, 'name' => 'College of Engineering']
        );

        $applied = College::updateOrCreate(
            ['university_id' => $astu->id, 'name' => 'College of Applied Sciences']
        );

        // ---------------- Departments ----------------
        $software = Department::updateOrCreate(
            ['college_id' => $engineering->id, 'name' => 'Software Engineering'],
            ['type' => 'degree_granting']
        );

        $electrical = Department::updateOrCreate(
            ['college_id' => $engineering->id, 'name' => 'Electrical Engineering'],
            ['type' => 'degree_granting']
        );

        $mathematics = Department::updateOrCreate(
            ['college_id' => $engineering->id, 'name' => 'Mathematics'],
            ['type' => 'service_only'] // owns service courses, grants no program of its own
        );

        $chemistry = Department::updateOrCreate(
            ['college_id' => $applied->id, 'name' => 'Applied Chemistry'],
            ['type' => 'degree_granting']
        );

        // ---------------- Programs ----------------
        Program::updateOrCreate(
            ['department_id' => $software->id, 'name' => 'Software Engineering'],
            ['duration_years' => 5]
        );

        Program::updateOrCreate(
            ['department_id' => $electrical->id, 'name' => 'Electrical Engineering'],
            ['duration_years' => 5]
        );

        Program::updateOrCreate(
            ['department_id' => $chemistry->id, 'name' => 'Applied Chemistry'],
            ['duration_years' => 4]
        );

        // ---------------- Courses (kept small — just enough to visualize ownership) ----------------
        Course::updateOrCreate(
            ['code' => 'MATH201'],
            ['department_id' => $mathematics->id, 'name' => 'Discrete Mathematics', 'credit_hours' => 3]
        );

        Course::updateOrCreate(
            ['code' => 'SE301'],
            ['department_id' => $software->id, 'name' => 'Database Systems', 'credit_hours' => 4]
        );

        Course::updateOrCreate(
            ['code' => 'EE201'],
            ['department_id' => $electrical->id, 'name' => 'Circuit Analysis', 'credit_hours' => 3]
        );

        Course::updateOrCreate(
            ['code' => 'CHEM101'],
            ['department_id' => $chemistry->id, 'name' => 'General Chemistry', 'credit_hours' => 3]
        );
    }
}
