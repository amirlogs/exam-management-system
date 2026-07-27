<?php

namespace Database\Seeders;

use App\Models\TeachingAssignment;
use Illuminate\Database\Seeder;

class TeachingAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TeachingAssignment::create([
            'course_id' => 1, 
            'section_id' => 2,
            'instructor_id' => 4,
        ]);

        TeachingAssignment::create([
            'course_id' => 2,
            'section_id' => 1,
            'instructor_id' => 5,
        ]);
    }
}
