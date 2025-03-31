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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('topic');
            $table->text('remarks')->nullable();
            $table->string('status')->default('waiting');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('language')->default('japanese');
            $table->string('format_type');
            $table->json('custom_format_settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
