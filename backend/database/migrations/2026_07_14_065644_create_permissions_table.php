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
        Schema::create("permissions", function (Blueprint $table) {
            $table->id();
            $table->string("name")->unique();
            $table->string("category")->nullable();
            $table->text("description")->nullable();
            $table->boolean("is_system_defined")->default(true);
            $table
                ->foreignId("created_by")
                ->nullable()
                ->constrained("users")
                ->nullOnDelete();
            $table->timestamp("created_at")->nullable();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("permissions");
    }
};
