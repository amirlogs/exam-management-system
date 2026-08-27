<?php

namespace App\Commit;

use App\Models\Program;
use App\Models\Role;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Facades\Hash;

class StudentCommitter
{
    public static function commit(array $data, $importHistory): void
    {
        $context = $importHistory['context'] ?? [];

        $program = Program::where('code', $data['program_code'])->firstOrFail();
        $curriculum = $program->curriculums()->where('status', 'active')->firstOrFail();
        $section = Section::where('program_id', $program->id)
            ->where('semester_id', $context['semester_id'])
            ->where('year_level', $context['year_level'])
            ->where('name', $data['section_name'])
            ->firstOrFail();

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make('password'),
            'is_first_login' => true,
            'is_active' => true,
        ]);
        $studentRole = Role::where('name', 'student')->firstOrFail();
        
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $studentRole->id,
            'assigned_at' => now(),
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
