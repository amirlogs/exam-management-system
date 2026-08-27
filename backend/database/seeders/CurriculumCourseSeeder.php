<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use Illuminate\Database\Seeder;

class CurriculumCourseSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Active curricula
        |--------------------------------------------------------------------------
        */

        $se = Curriculum::whereHas(
            'program',
            fn ($query) => $query->where('code', 'SE')
        )->where('status', 'active')->firstOrFail();

        $cs = Curriculum::whereHas(
            'program',
            fn ($query) => $query->where('code', 'CS')
        )->where('status', 'active')->firstOrFail();

        $ee = Curriculum::whereHas(
            'program',
            fn ($query) => $query->where('code', 'EE')
        )->where('status', 'active')->firstOrFail();

        $chem = Curriculum::whereHas(
            'program',
            fn ($query) => $query->where('code', 'CHEM')
        )->where('status', 'active')->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Courses
        |--------------------------------------------------------------------------
        */

        $math201 = Course::where('code', 'MATH201')->firstOrFail();
        $math101 = Course::where('code', 'MATH101')->firstOrFail();

        $se301 = Course::where('code', 'SE301')->firstOrFail();
        $se201 = Course::where('code', 'SE201')->firstOrFail();

        $cs201 = Course::where('code', 'CS201')->firstOrFail();
        $cs301 = Course::where('code', 'CS301')->firstOrFail();

        $ee201 = Course::where('code', 'EE201')->firstOrFail();
        $ee301 = Course::where('code', 'EE301')->firstOrFail();

        $chem101 = Course::where('code', 'CHEM101')->firstOrFail();
        $chem201 = Course::where('code', 'CHEM201')->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Software Engineering
        |--------------------------------------------------------------------------
        */

        $this->course($se, $math201, 1, 1);
        $this->course($se, $math101, 1, 2);
        $this->course($se, $se201, 2, 1);
        $this->course($se, $se301, 3, 1);

        /*
        |--------------------------------------------------------------------------
        | Computer Science
        |--------------------------------------------------------------------------
        */

        $this->course($cs, $math201, 1, 1);
        $this->course($cs, $cs201, 1, 2);
        $this->course($cs, $cs301, 2, 1);
        $this->course($cs, $math101, 2, 2);

        /*
        |--------------------------------------------------------------------------
        | Electrical Engineering
        |--------------------------------------------------------------------------
        */

        $this->course($ee, $math201, 1, 1);
        $this->course($ee, $ee201, 1, 1);
        $this->course($ee, $ee301, 2, 1);
        $this->course($ee, $math101, 1, 2);

        /*
        |--------------------------------------------------------------------------
        | Applied Chemistry
        |--------------------------------------------------------------------------
        */

        $this->course($chem, $chem101, 1, 1);
        $this->course($chem, $math101, 1, 2);
        $this->course($chem, $chem201, 2, 1);
    }

    private function course(
        Curriculum $curriculum,
        Course $course,
        int $yearLevel,
        int $semesterNumber
    ): void {
        CurriculumCourse::updateOrCreate(
            [
                'curriculum_id' => $curriculum->id,
                'course_id' => $course->id,
            ],
            [
                'year_level' => $yearLevel,
                'semester_number' => $semesterNumber,
            ]
        );
    }
}
