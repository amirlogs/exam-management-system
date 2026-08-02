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
        Schema::create('import_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('type'); // students | instructors | sections | questions | enrollments
            $table->string('file_path'); // required — the job reads the raw file back from here
            $table->json('context')->nullable(); // type-specific info: {"semester_id":3} or {"course_id":5}
        
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->json('validated_data')->nullable(); // every row: {row, valid, data, errors}
        
            $table->string('status')->default('pending'); // pending | processing | ready_for_review | confirmed | failed
        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_histories');
    }
};
