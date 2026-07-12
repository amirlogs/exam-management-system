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
        Schema::create("courses", function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("code");
            $table
                ->foreignId("owning_department_id")
                ->constrained("departments")
                ->restrictOnDelete();
            $table
                ->foreignId("lead_instructor_id")
                ->nullable()
                ->constrained("instructors", "user_id")
                ->nullOnDelete();
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
        Schema::dropIfExists("courses");
    }
};
