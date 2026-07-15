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
        Schema::create("question_groups", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("course_id")
                ->constrained("courses")
                ->restrictOnDelete();
            $table->string("label");
            $table
                ->foreignId("created_by")
                ->constrained("instructors", "user_id")
                ->restrictOnDelete();
            $table
                ->enum("status", [
                    "draft",
                    "pending_approval",
                    "approved",
                    "reopened",
                ])
                ->default("draft");
            $table
                ->foreignId("approved_by")
                ->nullable()
                ->constrained("users")
                ->nullOnDelete();
            $table->timestamp("approved_at")->nullable();
            $table
                ->foreignId("reopened_by")
                ->nullable()
                ->constrained("users")
                ->nullOnDelete();
            $table->timestamp("reopened_at")->nullable();
            $table->text("reopen_reason")->nullable();
            $table->timestamp("created_at")->nullable();

            $table->unique(["course_id", "label"]);
        });
    } /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("question_packages");
    }
};
