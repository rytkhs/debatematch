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
        // room_usersテーブルの外部キー制約を修正
        Schema::table('room_users', function (Blueprint $table) {
            // 既存の外部キー制約を削除
            $table->dropForeign(['user_id']);

            // user_idをnullableに変更
            $table->unsignedBigInteger('user_id')->nullable()->change();

            // 新しい外部キー制約を追加（set null）
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // sessionsテーブルの外部キー制約を追加
        Schema::table('sessions', function (Blueprint $table) {
            // 既存のインデックスを削除
            $table->dropIndex(['user_id']);

            // 外部キー制約を追加
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_users', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->index('user_id');
        });
    }
};
