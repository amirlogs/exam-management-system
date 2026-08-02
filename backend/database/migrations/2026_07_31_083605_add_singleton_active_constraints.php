<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Global singleton — only one semester in the ENTIRE table can be active
        DB::statement("
            CREATE UNIQUE INDEX one_active_semester
            ON semesters ((1))
            WHERE status = 'active'
        ");

        // Scoped singleton — only one active curriculum PER PROGRAM
        DB::statement("
            CREATE UNIQUE INDEX one_active_curriculum_per_program
            ON curricula (program_id)
            WHERE status = 'active'
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS one_active_semester');
        DB::statement('DROP INDEX IF EXISTS one_active_curriculum_per_program');
    }
};
