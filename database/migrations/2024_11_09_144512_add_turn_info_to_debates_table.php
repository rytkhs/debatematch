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
        Schema::table('debates', function (Blueprint $table) {
            $table->integer('current_turn')->default(0);
            $table->timestamp('turn_end_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('debates', function (Blueprint $table) {
            $table->dropColumn(['current_turn', 'turn_end_time']);
        });
    }
};
