<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\Section;
use App\Models\Semester;
use Illuminate\Database\Seeder;

class SemesterSectionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Current semester
        |--------------------------------------------------------------------------
        */

        $current = Semester::updateOrCreate(
            [
                'academic_year' => '2026',
                'name' => 'Semester 1',
            ],
            [
                'term_number' => 1,
                'start_date' => '2026-09-01',
                'end_date' => '2027-01-15',
                'status' => 'active',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Previous semester
        |--------------------------------------------------------------------------
        */

        Semester::updateOrCreate(
            [
                'academic_year' => '2025',
                'name' => 'Semester 2',
            ],
            [
                'term_number' => 2,
                'start_date' => '2025-02-01',
                'end_date' => '2025-06-15',
                'status' => 'completed',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Programs
        |--------------------------------------------------------------------------
        */

        $se = Program::where('code', 'SE')->firstOrFail();
        $cs = Program::where('code', 'CS')->firstOrFail();
        $ee = Program::where('code', 'EE')->firstOrFail();
        $chem = Program::where('code', 'CHEM')->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Sections
        |--------------------------------------------------------------------------
        */

        $sections = [
            // Software Engineering
            [$se->id, $current->id, 1, 'A'],
            [$se->id, $current->id, 1, 'B'],
            [$se->id, $current->id, 3, 'A'],

            // Computer Science
            [$cs->id, $current->id, 1, 'A'],
            [$cs->id, $current->id, 1, 'B'],
            [$cs->id, $current->id, 2, 'A'],

            // Electrical Engineering
            [$ee->id, $current->id, 1, 'A'],
            [$ee->id, $current->id, 1, 'B'],
            [$ee->id, $current->id, 2, 'A'],

            // Applied Chemistry
            [$chem->id, $current->id, 1, 'A'],
            [$chem->id, $current->id, 2, 'A'],
        ];

        foreach ($sections as [
            $programId,
            $semesterId,
            $yearLevel,
            $name,
        ]) {
            Section::updateOrCreate(
                [
                    'program_id' => $programId,
                    'semester_id' => $semesterId,
                    'year_level' => $yearLevel,
                    'name' => $name,
                ]
            );
        }
    }
}
