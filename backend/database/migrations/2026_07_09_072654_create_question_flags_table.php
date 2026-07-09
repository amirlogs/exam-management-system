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
        Schema::create("question_flags", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("question_id")
                ->constrained("questions")
                ->cascadeOnDelete();
            $table
                ->foreignId("instructor_id")
                ->constrained("instructors", "user_id")
                ->cascadeOnDelete();
            $table->text("comment");
            $table->timestamp("created_at")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("question_flags");
    }
};
