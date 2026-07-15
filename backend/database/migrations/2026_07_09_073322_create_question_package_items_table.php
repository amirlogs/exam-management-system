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
        Schema::create("question_group_items", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("group_id")
                ->constrained("question_groups")
                ->cascadeOnDelete();
            $table
                ->foreignId("question_id")
                ->constrained("questions")
                ->restrictOnDelete();
            $table->enum("source", ["from_pool", "direct_upload"]);
            $table
                ->foreignId("added_by")
                ->constrained("instructors", "user_id")
                ->restrictOnDelete();
            $table->timestamp("added_at")->nullable();

            $table->unique(["group_id", "question_id"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("question_package_items");
    }
};
