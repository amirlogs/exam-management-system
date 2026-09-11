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
        Schema::create('practice_question_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->longText('input');
            $table->unsignedInteger('count')->default(3);
            $table->boolean('isNote')->default(false);
            $table->string('type');
            $table->string('difficulty');
            $table->string('file_path')->nullable();
            $table->json('context')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->json('validated_question')->nullable();
            $table->string('status')->default('pending'); // pending | processing | ready_for_review | confirmed | failed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_question_histories');
    }
};
