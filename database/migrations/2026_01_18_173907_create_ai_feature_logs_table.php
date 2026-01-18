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
        Schema::create('ai_feature_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_id')->unique()->comment('リクエスト識別子（UUID）');
            $table->string('feature_type', 50)->comment('機能種別: topic_generate, topic_info');
            $table->string('status', 20)->default('processing')->comment('処理状態: processing, success, failed');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('ユーザーID');
            $table->json('parameters')->nullable()->comment('リクエストパラメータ');
            $table->json('response_data')->nullable()->comment('レスポンスデータ（成功時）');
            $table->text('error_message')->nullable()->comment('エラーメッセージ（失敗時）');
            $table->unsignedSmallInteger('status_code')->nullable()->comment('HTTPステータスコード（100-599）');
            $table->timestamp('started_at')->useCurrent()->comment('処理開始日時');
            $table->timestamp('finished_at')->nullable()->comment('処理完了日時');
            $table->unsignedInteger('duration_ms')->nullable()->comment('処理時間（ミリ秒）');

            // インデックス
            $table->index(['feature_type', 'status', 'started_at'], 'idx_feature_status_started');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_feature_logs');
    }
};
