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
        Schema::create("students", function (Blueprint $table) {
            $table
                ->foreignId("user_id")
                ->primary()
                ->constrained("users")
                ->cascadeOnDelete();
            $table->string("university_id")->unique();
            $table
                ->foreignId("section_id")
                ->constrained("sections")
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("students");
    }
};
