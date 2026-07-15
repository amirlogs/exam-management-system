<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Course::create([
            'name' => 'Calculus I',
            'code' => 'MATH101',
            'owning_department_id' => 2,
            'lead_instructor_id' => null,
        ]);

        Course::create([
            'name' => 'Data Structures',
            'code' => 'CS201',
            'owning_department_id' => 1,
            'lead_instructor_id' => 5,
        ]);
    }
}
