<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $tables = [
        'universities', 'colleges', 'departments', 'programs', 'courses',
        'curricula', 'curriculum_courses', 'semesters', 'sections',
        'course_offerings', 'roles', 'permissions',
        'questions', 'exams', 'academic_calendars', 'grading_systems',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->softDeletes());
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropSoftDeletes());
        }
    }
};
