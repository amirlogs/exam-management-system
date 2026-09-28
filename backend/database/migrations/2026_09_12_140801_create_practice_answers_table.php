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
        Schema::create('practice_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_exam_id')->constrained('practice_exams')->cascadeOnDelete();
            $table->foreignId('practice_question_id')->constrained('practice_questions')->cascadeOnDelete();
            $table->foreignId('practice_exam_question_id')->nullable()->constrained('practice_exam_questions')->nullOnDelete();
            $table->foreignId('selected_option_id')->nullable()->constrained('practice_question_options')->nullOnDelete();
            $table->text('answer_text')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['practice_exam_id', 'practice_question_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_answers');
    }
};
