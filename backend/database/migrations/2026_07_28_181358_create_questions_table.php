<?php

use illuminate\database\migrations\migration;
use illuminate\database\schema\blueprint;
use illuminate\support\facades\schema;

return new class extends migration
{
    /**
     * run the migrations.
     */
    public function up(): void
    {
        schema::create('questions', function (blueprint $table) {
            $table->id();
            $table->foreignid('course_id')->constrained()->cascadeondelete();
            $table->foreignid('created_by')->constrained('users');
            $table->foreignid('import_history_id')->nullable()->constrained('import_histories')->nullondelete();
            $table->enum('type', ['mcq', 'true_false', 'essay', 'short_answer']);
            $table->string('chapter')->nullable();
            $table->text('content');
            $table->string('difficulty')->nullable();
            $table->string('status')->default('active'); // / draft | active| archived
            $table->timestamps();
        });
    }

    /**
     * reverse the migrations.
     */
    public function down(): void
    {
        schema::dropifexists('questions');
    }
};
