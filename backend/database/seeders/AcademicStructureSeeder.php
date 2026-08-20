<?php

namespace Database\Seeders;

use App\Models\College;
use App\Models\Course;
use App\Models\Curriculum;
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
            ['name' => 'Adama Science and Technology University', 'address' => 'Adama , Ethiopia']
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

        // ---------------- Programs (now with `code` — required by StudentValidator / SectionValidator) ----------------
        $se = Program::updateOrCreate(
            ['code' => 'SE'],
            ['department_id' => $software->id, 'name' => 'Software Engineering', 'duration_years' => 5]
        );

        $ee = Program::updateOrCreate(
            ['code' => 'EE'],
            ['department_id' => $electrical->id, 'name' => 'Electrical Engineering', 'duration_years' => 5]
        );

        $chem = Program::updateOrCreate(
            ['code' => 'CHEM'],
            ['department_id' => $chemistry->id, 'name' => 'Applied Chemistry', 'duration_years' => 4]
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

        // ---------------- Curriculums (required — StudentValidator now rejects any program with no active curriculum) ----------------
        Curriculum::updateOrCreate(
            ['program_id' => $se->id, 'version' => '2025'],
            ['status' => 'active']
        );

        Curriculum::updateOrCreate(
            ['program_id' => $ee->id, 'version' => '2025'],
            ['status' => 'active']
        );

        Curriculum::updateOrCreate(
            ['program_id' => $chem->id, 'version' => '2025'],
            ['status' => 'active']
        );
    }
}
