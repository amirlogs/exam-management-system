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
        Schema::create('question_bank_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->restrictOnDelete();
            $table->enum('status', ['pending', 'processing', 'ready_for_review', 'failed', 'confirmed', 'approved'])->default('pending');
            $table->foreignId('uploaded_by')->constrained('instructors', 'user_id')->restrictOnDelete();
            $table->string('file_path')->nullable();
            $table->unsignedInteger('valid_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->text('failure_reason')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('instructors', 'user_id')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->json('validated_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_bank_imports');
    }
};
