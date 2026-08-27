<?php

namespace Database\Seeders;

use App\Models\Curriculum;
use App\Models\Program;
use Illuminate\Database\Seeder;

class CurriculumSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            'SE',
            'CS',
            'EE',
            'CHEM',
        ];

        foreach ($programs as $code) {
            $program = Program::where('code', $code)->firstOrFail();

            Curriculum::updateOrCreate(
                [
                    'program_id' => $program->id,
                    'version' => '2025',
                ],
                [
                    'status' => 'active',
                ]
            );
        }
    }
}