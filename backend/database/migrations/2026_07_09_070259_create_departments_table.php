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
        Schema::create("departments", function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->enum("type", ["degree_granting", "service_only"]);
            $table
                ->foreignId("head_user_id")
                ->nullable()
                ->constrained("users")
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
        Schema::dropIfExists("departments");
    }
};
