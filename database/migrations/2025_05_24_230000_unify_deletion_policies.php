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
        /*
         * 削除ポリシーの統一設計原則:
         *
         * 1. ユーザー削除時 → SET NULL (履歴保持)
         *    - ディベート履歴、メッセージ履歴は重要な資産
         *    - 削除されたユーザーは "unknown" として表示
         *    - withTrashed() でソフトデリートされたユーザー情報も取得可能
         *
         * 2. リソース削除時 → CASCADE (整合性維持)
         *    - Room削除 → 関連するDebate、RoomUserも削除
         *    - Debate削除 → 関連するMessage、Evaluationも削除
         *    - 親リソースなしでは意味をなさない子リソースを自動削除
         *
         * 3. セッション・ログ系 → SET NULL (監査証跡保持)
         *    - 接続ログ、セッション情報は監査・分析に重要
         */

        // debate_evaluationsテーブル: cascadeOnDeleteをonDelete('cascade')に統一
        Schema::table('debate_evaluations', function (Blueprint $table) {
            $table->dropForeign(['debate_id']);
            $table->foreign('debate_id')->references('id')->on('debates')->onDelete('cascade');
        });

        /*
         * 統一後の削除ポリシー確認:
         *
         * ユーザー関連 (SET NULL):
         * - users → rooms.created_by
         * - users → room_users.user_id
         * - users → debates.affirmative_user_id
         * - users → debates.negative_user_id
         * - users → debate_messages.user_id
         * - users → connection_logs.user_id
         * - users → sessions.user_id
         *
         * リソース階層 (CASCADE):
         * - rooms → room_users.room_id
         * - rooms → debates.room_id
         * - debates → debate_messages.debate_id
         * - debates → debate_evaluations.debate_id
         */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('debate_evaluations', function (Blueprint $table) {
            $table->dropForeign(['debate_id']);
            $table->foreign('debate_id')->references('id')->on('debates')->cascadeOnDelete();
        });
    }
};
