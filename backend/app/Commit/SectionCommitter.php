<?php

namespace App\Commit;

use App\Models\Program;
use App\Models\Section;

class SectionCommitter
{
    public static function commit(array $data, $importHistory): void
    {
        $context = $importHistory['context'] ?? [];
        $program = Program::where('code', $data['program_code'])->firstOrFail();

        Section::create([
            'program_id' => $program->id,
            'semester_id' => $context['semester_id'],
            'year_level' => $data['year_level'],
            'name' => $data['name'],
        ]);
    }
}
