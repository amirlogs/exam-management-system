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
        Schema::create('practice_exams', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedSmallInteger('duration_minutes');
            $table->json('composition')->nullable();
            $table->unsignedInteger('total_marks')->default(0);
            $table->unsignedInteger('total_questions')->default(0);
            $table->string('status')->default('draft');
            $table->foreignId('owned_by')->constrained('users');
            $table->timestamp('ended_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_exams');
    }
};
