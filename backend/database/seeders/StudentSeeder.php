<?php

namespace Database\Seeders;

use App\Models\Curriculum;
use App\Models\Program;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $se = Program::where('code', 'SE')->firstOrFail();
        $cs = Program::where('code', 'CS')->firstOrFail();
        $ee = Program::where('code', 'EE')->firstOrFail();

        $seCurriculum = Curriculum::where(
            'program_id',
            $se->id
        )->where('status', 'active')->firstOrFail();

        $csCurriculum = Curriculum::where(
            'program_id',
            $cs->id
        )->where('status', 'active')->firstOrFail();

        $eeCurriculum = Curriculum::where(
            'program_id',
            $ee->id
        )->where('status', 'active')->firstOrFail();

        $students = [
            [
                'email' => 'student.se1@uems.test',
                'student_number' => 'UGR-2026-0001',
                'program_id' => $se->id,
                'curriculum_id' => $seCurriculum->id,
                'section_name' => 'A',
                'year_level' => 1,
            ],
            [
                'email' => 'student.se2@uems.test',
                'student_number' => 'UGR-2026-0002',
                'program_id' => $se->id,
                'curriculum_id' => $seCurriculum->id,
                'section_name' => 'B',
                'year_level' => 1,
            ],
            [
                'email' => 'student.cs1@uems.test',
                'student_number' => 'UGR-2026-0101',
                'program_id' => $cs->id,
                'curriculum_id' => $csCurriculum->id,
                'section_name' => 'A',
                'year_level' => 1,
            ],
            [
                'email' => 'student.ee1@uems.test',
                'student_number' => 'UGR-2026-0201',
                'program_id' => $ee->id,
                'curriculum_id' => $eeCurriculum->id,
                'section_name' => 'A',
                'year_level' => 1,
            ],
        ];

        foreach ($students as $data) {
            $user = User::where(
                'email',
                $data['email']
            )->firstOrFail();

            $section = Section::where('program_id', $data['program_id'])
                ->where('year_level', $data['year_level'])
                ->where('name', $data['section_name'])
                ->whereHas('semester', fn ($query) => $query->where('status', 'active')
                )
                ->firstOrFail();

            Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'student_number' => $data['student_number'],
                    'program_id' => $data['program_id'],
                    'section_id' => $section->id,
                    'curriculum_id' => $data['curriculum_id'],
                    'entry_year' => 2026,
                    'status' => 'active',
                ]
            );
        }
    }
}
