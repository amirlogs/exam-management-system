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
        Schema::create('practice_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignid('owned_by')->constrained('users');
            $table->foreignid('practice_question_histories_id')->constrained('practice_question_histories');
            $table->enum('type', ['mcq', 'true_false', 'essay', 'short_answer']);
            $table->text('content');
            $table->string('difficulty')->nullable();
            $table->string('status')->default('active'); // / draft | active| archived
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_questions');
    }
};
