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
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('import_history_id')->nullable()->constrained('import_histories')->nullOnDelete();
            $table->enum('type', ['MCQ', 'TRUE_FALSE', 'ESSAY', 'SHORT_ANSWER']);
            $table->string('chapter')->nullable();
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
        Schema::dropIfExists('questions');
    }
};
