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
        // ---------------- Semesters ----------------
        $current = Semester::updateOrCreate(
            ['academic_year' => '2026', 'name' => 'Semester 1'],
            ['term_number' => 1, 'start_date' => '2026-09-01', 'end_date' => '2027-01-15', 'status' => 'active']
        );

        $past = Semester::updateOrCreate(
            ['academic_year' => '2025', 'name' => 'Semester 2'],
            ['term_number' => 2, 'start_date' => '2025-02-01', 'end_date' => '2025-06-15', 'status' => 'completed']
        );

        // ---------------- Sections — multiple years, matching StudentValidator's
        // (program + semester + year_level + name) lookup key ----------------
        $se = Program::where('code', 'SE')->firstOrFail();
        $ee = Program::where('code', 'EE')->firstOrFail();
        $chem = Program::where('code', 'CHEM')->firstOrFail();

        $sections = [
            // Software Engineering — 2 sections in year 1, 1 section in year 3
            [$se->id, $current->id, 1, '1'],
            [$se->id, $current->id, 1, '2'],
            [$se->id, $current->id, 3, '1'],

            // Electrical Engineering — year 1 only
            [$ee->id, $current->id, 1, '1'],

            // Applied Chemistry — year 1 only
            [$chem->id, $current->id, 1, '1'],
        ];

        foreach ($sections as [$programId, $semesterId, $yearLevel, $name]) {
            Section::updateOrCreate(
                ['program_id' => $programId, 'semester_id' => $semesterId, 'year_level' => $yearLevel, 'name' => $name]
            );
        }
    }
}
