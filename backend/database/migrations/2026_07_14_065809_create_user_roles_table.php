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
        Schema::create("user_roles", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("user_id")
                ->constrained("users")
                ->cascadeOnDelete();
            $table
                ->foreignId("role_id")
                ->constrained("roles")
                ->restrictOnDelete();
            $table
                ->foreignId("department_id")
                ->nullable()
                ->constrained("departments")
                ->restrictOnDelete();
            $table
                ->foreignId("assigned_by")
                ->constrained("users")
                ->restrictOnDelete();
            $table->timestamp("assigned_at")->nullable();

            $table->unique(["user_id", "role_id", "department_id"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("user_roles");
    }
};
