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
        Schema::create('exam_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('cycle');
            $table->foreignId('reviewed_by')->constrained('users');
            $table->string('decision'); // 'approved' | 'rejected'
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'cycle']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_approvals');
    }
};
