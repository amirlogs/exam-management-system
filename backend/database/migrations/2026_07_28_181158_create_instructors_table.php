<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('instructors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id') ->unique() ->constrained('users') ->cascadeOnDelete();
            $table->foreignId('department_id') ->constrained('departments') ->cascadeOnDelete();
            $table->string('employee_number')->unique();
            $table->string('academic_rank')->nullable();
            $table->string('status')->default('active');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructors');
    }
};
