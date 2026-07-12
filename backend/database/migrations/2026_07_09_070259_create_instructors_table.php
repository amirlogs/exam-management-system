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
        Schema::create("instructors", function (Blueprint $table) {
            $table
                ->foreignId("user_id")
                ->primary()
                ->constrained("users")
                ->cascadeOnDelete();
            $table->string("staff_id")->unique();
            $table->timestamps();
            $table
                ->foreignId("home_department_id")
                ->constrained("departments")
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("instructors");
    }
};
