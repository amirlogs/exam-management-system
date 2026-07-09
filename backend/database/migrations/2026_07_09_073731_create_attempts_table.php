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
        Schema::create("attempts", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("session_id")
                ->constrained("exam_sessions")
                ->restrictOnDelete();
            $table
                ->foreignId("student_id")
                ->constrained("students", "user_id")
                ->cascadeOnDelete();
            $table
                ->enum("status", ["in_progress", "submitted", "auto_submitted"])
                ->default("in_progress");
            $table->json("randomized_question_order")->nullable();
            $table->timestamp("started_at")->nullable();
            $table->timestamp("submitted_at")->nullable();

            $table->unique(["session_id", "student_id"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("attempts");
    }
};
