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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->string('name');
            $table->string('email');
            $table->string('subject', 500);
            $table->text('message');
            $table->enum('status', ['new', 'in_progress', 'replied', 'resolved', 'closed'])->default('new');
            $table->string('language', 2)->default('ja');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->text('admin_notes')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
