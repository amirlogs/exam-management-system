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
        Schema::create("answers", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("attempt_id")
                ->constrained("attempts")
                ->cascadeOnDelete();
            $table
                ->foreignId("question_id")
                ->constrained("questions")
                ->restrictOnDelete();
            $table->text("response")->nullable();
            $table->timestamp("submitted_at")->nullable();
            $table->boolean("is_auto_graded")->default(false);
            $table->decimal("score", 5, 2)->nullable();
            $table
                ->foreignId("graded_by")
                ->nullable()
                ->constrained("instructors", "user_id")
                ->nullOnDelete();
            $table->timestamp("graded_at")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("answers");
    }
};
