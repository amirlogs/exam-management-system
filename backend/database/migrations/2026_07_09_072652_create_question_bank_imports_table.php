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
        Schema::create("question_bank_imports", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("question_bank_id")
                ->constrained("question_banks")
                ->restrictOnDelete();
            $table->enum("status", ["pending", "confirmed"]);
            $table
                ->foreignId("uploaded_by")
                ->constrained("instructors", "user_id")
                ->restrictOnDelete();
            $table
                ->foreignId("confirmed_by")
                ->nullable()
                ->constrained("instructors", "user_id")
                ->nullOnDelete();
            $table->timestamp("confirmed_at")->nullable();
            $table
                ->foreignId("approved_by")
                ->nullable()
                ->constrained("users")
                ->nullOnDelete();
            $table->timestamp("approved_at")->nullable();
            $table->timestamp("created_at")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("question_bank_imports");
    }
};
