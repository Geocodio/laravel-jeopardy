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
        Schema::create('lightning_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->text('question_text');
            $table->text('answer_text');
            $table->integer('order_position');
            $table->boolean('is_current')->default(false);
            $table->boolean('is_answered')->default(false);
            $table->foreignId('answered_by_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lightning_questions');
    }
};
