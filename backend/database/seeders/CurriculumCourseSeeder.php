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
        $seCurriculum = Curriculum::whereHas('program', fn ($q) => $q->where('code', 'SE'))->firstOrFail();
        $eeCurriculum = Curriculum::whereHas('program', fn ($q) => $q->where('code', 'EE'))->firstOrFail();
        $chemCurriculum = Curriculum::whereHas('program', fn ($q) => $q->where('code', 'CHEM'))->firstOrFail();

        $math = Course::where('code', 'MATH201')->firstOrFail();
        $se301 = Course::where('code', 'SE301')->firstOrFail();
        $ee201 = Course::where('code', 'EE201')->firstOrFail();
        $chem101 = Course::where('code', 'CHEM101')->firstOrFail();

        // SE curriculum: MATH201 in year 1, SE301 in year 3 (Mathematics is service_only —
        // it owns the course, but SE students take it via THEIR curriculum, not Math's)
        CurriculumCourse::updateOrCreate(['curriculum_id' => $seCurriculum->id, 'course_id' => $math->id], ['year_level' => 1, 'semester_number' => 1]);
        CurriculumCourse::updateOrCreate(['curriculum_id' => $seCurriculum->id, 'course_id' => $se301->id], ['year_level' => 3, 'semester_number' => 1]);

        // EE curriculum: also requires MATH201 (same course, reused across two curricula), plus EE201
        CurriculumCourse::updateOrCreate(['curriculum_id' => $eeCurriculum->id, 'course_id' => $math->id], ['year_level' => 1, 'semester_number' => 1]);
        CurriculumCourse::updateOrCreate(['curriculum_id' => $eeCurriculum->id, 'course_id' => $ee201->id], ['year_level' => 1, 'semester_number' => 1]);

        // CHEM curriculum: CHEM101
        CurriculumCourse::updateOrCreate(['curriculum_id' => $chemCurriculum->id, 'course_id' => $chem101->id], ['year_level' => 1, 'semester_number' => 1]);
    }
}
