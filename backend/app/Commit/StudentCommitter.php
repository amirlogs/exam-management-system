<?php

namespace App\Commit;

use App\Models\Program;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StudentCommitter
{
    public static function commit(array $data, $importHistory): void
    {
        $context = $importHistory['context'] ?? [];
        $program = Program::where('code', $data['program_code'])->first();
        $curriculum = $program->curriculums()->where('status', 'active')->first();
        $section = Section::where('program_id', $program->id)
            ->where('semester_id', $context['semester_id'])
            ->where('year_level', $context['year_level'])
            ->where('name', $data['section_name'])
            ->first();

        // email randomly generated and sent to email using the studetn email
        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            // 'password' => Hash::make(Str::random(12)),
            'password' => Hash::make('password'),
            'is_first_login' => true,
        ]);

        Student::create([
            'user_id' => $user->id,
            'student_number' => $data['student_number'],
            'program_id' => $program->id,
            'section_id' => $section->id,
            'curriculum_id' => $curriculum->id,
            'entry_year' => $data['entry_year'],
            'status' => 'active',
        ]);
    }
}
