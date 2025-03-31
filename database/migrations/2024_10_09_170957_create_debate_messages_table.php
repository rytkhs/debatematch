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
        Schema::create('debate_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debate_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->text('message');
            $table->tinyInteger('turn')->unsigned();
            $table->timestamps();
            $table->softDeletes();

            // インデックスの追加
            $table->index('debate_id');
            $table->index(['debate_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debate_messages');
    }
};
