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
        Schema::create("user_role_permissions", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("user_role_id")
                ->constrained("user_roles")
                ->cascadeOnDelete();
            $table
                ->foreignId("permission_id")
                ->constrained("permissions")
                ->cascadeOnDelete();
            $table->boolean("is_granted")->default(true);
            $table
                ->foreignId("set_by")
                ->constrained("users")
                ->restrictOnDelete();
            $table->timestamp("set_at")->nullable();

            $table->unique(["user_role_id", "permission_id"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("user_role_permissions");
    }
};
