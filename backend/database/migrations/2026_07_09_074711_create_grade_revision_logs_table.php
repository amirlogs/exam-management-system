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
        Schema::create("grade_revision_logs", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("answer_id")
                ->constrained("answers")
                ->restrictOnDelete();
            $table
                ->foreignId("actor_id")
                ->constrained("users")
                ->restrictOnDelete();
            $table->decimal("previous_value", 5, 2)->nullable();
            $table->decimal("new_value", 5, 2)->nullable();
            $table->text("reason")->nullable();
            $table->timestamp("created_at")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("grade_revision_logs");
    }
};
