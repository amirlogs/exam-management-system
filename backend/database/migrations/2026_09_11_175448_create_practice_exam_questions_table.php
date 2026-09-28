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
        Schema::create('practice_exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practice_question_id')->constrained()->cascadeOnDelete();

            $table->timestamps();
            $table->unique(['practice_exam_id', 'practice_question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_exam_questions');
    }
};
