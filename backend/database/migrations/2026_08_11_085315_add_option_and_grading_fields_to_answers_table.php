<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->foreignId('selected_option_id')->nullable()->after('exam_question_id')->constrained('question_options')->nullOnDelete();
            $table->boolean('is_correct')->nullable()->after('answer_text');
            $table->timestamp('graded_at')->nullable()->after('graded_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            //
        });
    }
};
