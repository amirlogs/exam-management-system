<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,

            AcademicStructureSeeder::class,
            CurriculumSeeder::class,
            CurriculumCourseSeeder::class,

            SemesterSectionSeeder::class,

            UserSeeder::class,
            InstructorSeeder::class,
            StudentSeeder::class,

            CourseOfferingSeeder::class,
        ]);
    }
}
