<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("teaching_assignments", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("course_id")
                ->constrained("courses")
                ->restrictOnDelete();
            $table
                ->foreignId("section_id")
                ->constrained("sections")
                ->restrictOnDelete();
            $table
                ->foreignId("instructor_id")
                ->constrained("instructors", "user_id")
                ->restrictOnDelete();
            $table->softDeletes();
            $table
                ->foreignId("deleted_by")
                ->nullable()
                ->constrained("users")
                ->nullOnDelete();

            $table->unique(["course_id", "section_id"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("teaching_assignments");
    }
};
