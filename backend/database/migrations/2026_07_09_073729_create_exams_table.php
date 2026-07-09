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
         Schema::create('exams', function (Blueprint $table) {
             $table->id();
             $table->foreignId('course_id')->constrained('courses')->restrictOnDelete();
             $table->enum('type', ['mid', 'final']);
             $table->integer('question_count');
             $table->boolean('randomization_enabled')->default(true);
             $table->decimal('total_score', 5, 2);
             $table->timestamp('created_at')->nullable();
             $table->softDeletes();
             $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
         });
     }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
