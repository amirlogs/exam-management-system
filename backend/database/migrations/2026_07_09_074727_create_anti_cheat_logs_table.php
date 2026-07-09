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
        Schema::create("anticheat_logs", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("attempt_id")
                ->constrained("attempts")
                ->cascadeOnDelete();
            $table->enum("event_type", ["tab_switch", "focus_loss"]);
            $table->timestamp("occurred_at");
            $table->boolean("escalated")->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("anti_cheat_logs");
    }
};
