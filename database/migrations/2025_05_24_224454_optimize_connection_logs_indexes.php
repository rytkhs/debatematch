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
        Schema::table('connection_logs', function (Blueprint $table) {
            $table->dropIndex(['context_type', 'context_id']);

            $table->index(['user_id', 'context_type', 'context_id', 'created_at'], 'idx_latest_log_lookup');

            $table->index(['status', 'context_type', 'created_at'], 'idx_status_stats');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('connection_logs', function (Blueprint $table) {
            $table->index(['context_type', 'context_id']);

            $table->dropIndex('idx_latest_log_lookup');
            $table->dropIndex('idx_status_stats');
        });
    }
};
