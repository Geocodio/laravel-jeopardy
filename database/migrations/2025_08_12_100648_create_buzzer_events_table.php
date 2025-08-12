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
        Schema::create('buzzer_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('clue_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lightning_question_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('buzzed_at');
            $table->boolean('is_first')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buzzer_events');
    }
};
