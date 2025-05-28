<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER DATABASE `' . env('DB_DATABASE', 'laravel') . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $tables = [
            'users',
            'password_reset_tokens',
            'sessions',
            'rooms',
            'room_users',
            'debates',
            'debate_messages',
            'debate_evaluations',
            'connection_logs',
            'cache',
            'cache_locks',
            'jobs',
            'job_batches',
            'failed_jobs',
            'migrations'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::statement("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER DATABASE `' . env('DB_DATABASE', 'laravel') . '` CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');

        $tables = [
            'users',
            'password_reset_tokens',
            'sessions',
            'rooms',
            'room_users',
            'debates',
            'debate_messages',
            'debate_evaluations',
            'connection_logs',
            'cache',
            'cache_locks',
            'jobs',
            'job_batches',
            'failed_jobs',
            'migrations'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::statement("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci");
            }
        }
    }
};
