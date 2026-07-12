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
        Schema::create("questions", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("question_bank_id")
                ->constrained("question_banks")
                ->restrictOnDelete();
            $table
                ->foreignId("import_id")
                ->nullable()
                ->constrained("question_bank_imports")
                ->nullOnDelete();
            $table->enum("type", [
                "mcq",
                "true_false",
                "essay",
                "short_answer",
            ]);
            $table->text("text");
            $table->json("options")->nullable();
            $table->text("correct_answer")->nullable();
            $table->string("difficulty")->nullable();
            $table->decimal("points", 5, 2);
            $table->boolean("is_active")->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table
                ->foreignId("deleted_by")
                ->nullable()
                ->constrained("users")
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("questions");
    }
};
