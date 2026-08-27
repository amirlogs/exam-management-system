<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseInstructor;
use App\Models\CourseOffering;
use App\Models\Instructor;
use App\Models\Section;
use App\Models\Semester;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CourseOfferingSeeder extends Seeder
{
    public function run(): void
    {
        $semester = Semester::where('status', 'active')
            ->where('term_number', 1)
            ->firstOrFail();

        $admin = \App\Models\User::where(
            'email',
            'universityadmin@uems.test'
        )->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Course offerings
        |--------------------------------------------------------------------------
        */

        $math = Course::where('code', 'MATH201')->firstOrFail();
        $database = Course::where('code', 'SE301')->firstOrFail();
        $circuit = Course::where('code', 'EE201')->firstOrFail();

        $this->createOffering(
            $math,
            $semester,
            $admin->id,
            'approved'
        );

        $this->createOffering(
            $database,
            $semester,
            $admin->id,
            'draft'
        );

        $this->createOffering(
            $circuit,
            $semester,
            $admin->id,
            'approved'
        );
    }

    private function createOffering(
        Course $course,
        Semester $semester,
        int $createdBy,
        string $status
    ): CourseOffering {
        $offering = CourseOffering::updateOrCreate(
            [
                'course_id' => $course->id,
                'semester_id' => $semester->id,
            ],
            [
                'created_by' => $createdBy,
                'status' => $status,
                'rejection_reason' => null,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Determine sections from active curricula
        |--------------------------------------------------------------------------
        */

        $curriculumCourses = \App\Models\CurriculumCourse::query()
            ->where('course_id', $course->id)
            ->where(
                'semester_number',
                $semester->term_number
            )
            ->whereHas(
                'curriculum',
                fn ($query) => $query->where('status', 'active')
            )
            ->with('curriculum')
            ->get();

        $sectionIds = collect();

        foreach ($curriculumCourses as $curriculumCourse) {
            $ids = Section::where(
                'program_id',
                $curriculumCourse->curriculum->program_id
            )
                ->where(
                    'year_level',
                    $curriculumCourse->year_level
                )
                ->where(
                    'semester_id',
                    $semester->id
                )
                ->pluck('id');

            $sectionIds = $sectionIds->merge($ids);
        }

        $offering->sections()->sync(
            $sectionIds->unique()->values()->all()
        );

        return $offering->refresh();
    }
}