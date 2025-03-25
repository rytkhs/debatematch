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
        Schema::create('debate_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debate_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_analyzable')->default(true);
            $table->enum('winner', ['affirmative', 'negative'])->nullable();
            $table->text('analysis')->nullable();
            $table->text('reason')->nullable();
            $table->text('feedback_for_affirmative')->nullable();
            $table->text('feedback_for_negative')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debate_evaluations');
    }
};
