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
        Schema::create('debates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignId('affirmative_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('negative_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('current_turn')->default(0);
            $table->timestamp('turn_end_time')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debates');
    }
};
