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
            $table->enum('type', ['MCQ', 'TRUE_FALSE', 'ESSAY', 'SHORT_ANSWER']);
            $table->string('chapter')->nullable();
            $table->text('content');
            $table->string('difficulty')->nullable();
            $table->string('status')->default('draft'); // draft, pending_approval, approved, rejected, archived
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
