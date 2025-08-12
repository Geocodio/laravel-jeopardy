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
        Schema::create('clues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->text('question_text');
            $table->text('answer_text');
            $table->integer('value');
            $table->boolean('is_daily_double')->default(false);
            $table->boolean('is_revealed')->default(false);
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
        Schema::dropIfExists('clues');
    }
};
