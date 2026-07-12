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
        Schema::create("sections", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("home_department_id")
                ->constrained("departments")
                ->restrictOnDelete();
            $table->integer("year_level");
            $table->string("section_label");
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
        Schema::dropIfExists("sections");
    }
};
