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
        Schema::create("results", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("attempt_id")
                ->unique()
                ->constrained("attempts")
                ->cascadeOnDelete();
            $table->decimal("total_score", 5, 2)->nullable();
            $table->timestamp("released_at")->nullable();
            $table->timestamps();
            $table
                ->foreignId("released_by")
                ->nullable()
                ->constrained("instructors", "user_id")
                ->nullOnDelete();
            $table->timestamp("edit_window_closes_at")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("results");
    }
};
