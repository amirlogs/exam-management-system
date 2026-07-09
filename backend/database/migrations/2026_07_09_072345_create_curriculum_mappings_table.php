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
        Schema::create("curriculum_mappings", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("department_id")
                ->constrained("departments")
                ->restrictOnDelete();
            $table->integer("year_level");
            $table->enum("semester", [1, 2]);
            $table
                ->foreignId("course_id")
                ->constrained("courses")
                ->restrictOnDelete();
        });
    } /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("curriculum_mappings");
    }
};
