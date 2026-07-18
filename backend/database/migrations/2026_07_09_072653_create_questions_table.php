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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->restrictOnDelete();
            $table->foreignId('import_id')->nullable()->constrained('question_bank_imports')->restrictOnDelete();
            $table->enum('type', ['mcq', 'true_false', 'essay', 'short_answer']);
            $table->text('text');
            $table->json('options')->nullable();
            $table->text('correct_answer')->nullable();
            $table->string('difficulty')->nullable();
            $table->decimal('points', 8, 2);
            $table->enum('status', ['draft', 'confirmed', 'approved'])->default('draft');
            $table->boolean('is_active')->default(false);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
