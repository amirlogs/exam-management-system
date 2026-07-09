<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $table = "exam_sessions";
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("exam_sessions", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("exam_id")
                ->constrained("exams")
                ->restrictOnDelete();
            $table
                ->foreignId("section_id")
                ->constrained("sections")
                ->restrictOnDelete();
            $table
                ->foreignId("question_package_id")
                ->constrained("question_packages")
                ->restrictOnDelete();
            $table->string("lab_name");
            $table->timestamp("scheduled_start");
            $table->timestamp("scheduled_end");
            $table->timestamp("actual_start")->nullable();
            $table->timestamp("actual_end")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("exam_sessions");
    }
};
