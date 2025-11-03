<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\ion\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Room;
use App\Models\Debate;
use App\Models\DebateMessage;
use App\Models\DebateEvaluation;
use Illuminate\Support\Facades\Config;

/**
 * DebateReportCopyTest - マークダウンコピー機能の統合テスト
 *
 * resultページとrecordsページでのコピーボタン表示、
 * データ注入の正確性、権限チェックをテストする
 */
class DebateReportCopyTest extends TestCase
{
    use RefreshDatabase;

    private User $affirmativeUser;
    private User $negativeUser;
    private User $otherUser;
    private User $aiUser;
    private Debate $debate;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用ユーザーの作成
        $this->affirmativeUser = User::factory()->create([
            'name' => 'Affirmative User',
            'email' => 'affirmative@test.com'
        ]);

        $this->negativeUser = User::factory()->create([
            'name' => 'Negative User',
            'email' => 'negative@test.com'
        ]);

        $this->otherUser = User::factory()->create([
            'name' => 'Other User',
            'email' => 'other@test.com'
        ]);

        $this->aiUser = User::factory()->create([
            'name' => 'AI User',
            'email' => 'ai@test.com'
        ]);

        Config::set('app.ai_user_id', $this->aiUser->id);

        // テスト用ルームとディベートの作成
        $this->room = Room::factory()->create([
            'name' => 'Test Room',
            'topic' => 'Should AI be regulated?',
            'remarks' => 'Test remarks for debate',
            'status' => Room::STATUS_FINISHED,
            'created_by' => $this->affirmativeUser->id,
        ]);

        $this->debate = Debate::factory()->create([
            'room_id' => $this->room->id,
            'affirmative_user_id' => $this->affirmativeUser->id,
            'negative_user_id' => $this->negativeUser->id,
        ]);

        // テスト用メッセージの作成
        DebateMessage::factory()->create([
            'debate_id' => $this->debate->id,
            'user_id' => $this->affirmativeUser->id,
            'turn' => 1,
            'message' => 'Test message from affirmative',
        ]);

        DebateMessage::factory()->create([
            'debate_id' => $this->debate->id,
            'user_id' => $this->negativeUser->id,
            'turn' => 2,
            'message' => 'Test message from negative',
        ]);

        // テスト用評価データの作成
        DebateEvaluation::factory()->create([
            'debate_id' => $this->debate->id,
            'is_analyzable' => true,
            'winner' => 'affirmative',
            'analysis' => 'Test analysis content',
            'reason' => 'Test reason content',
            'feedback_for_affirmative' => 'Test feedback for affirmative',
            'feedback_for_negative' => 'Test feedback for negative',
        ]);
    }

    /**
     * Test: resultページでコピーボタンが表示される
     * Requirements: 1.1, 5.1
     */
    public function test_copy_button_is_displayed_on_result_page(): void
    {
        $response = $this->actingAs($this->affirmativeUser)
            ->get(route('debate.result', $this->debate));

        $response->assertStatus(200);

        // コピーボタンの存在を確認（翻訳キーで確認）
        $response->assertSee(__('records.copy_report'), false);
    }

    /**
     * Test: recordsページでコピーボタンが表示される
     * Requirements: 1.1, 5.1
     */
    public function test_copy_button_is_displayed_on_records_page(): void
    {
        $response = $this->actingAs($this->affirmativeUser)
            ->get(route('records.show', $this->debate));

        $response->assertStatus(200);

        // コピーボタンの存在を確認
        $response->assertSee(__('records.copy_report'), false);
    }

    /**
     * Test: resultページでディベートデータが正しく注入される
     * Requirements: 4.1, 4.2, 4.3, 4.4
     */
    public function test_debate_data_is_correctly_injected_on_result_page(): void
    {
        $response = $this->actingAs($this->affirmativeUser)
            ->get(route('debate.result', $this->debate));

        $response->assertStatus(200);

        // window.debateData の存在を確認
        $response->assertSee('window.debateData', false);

        // 基本的なディベート情報の注入を確認
        $response->assertSee($this->debate->room->topic, false);
        $response->assertSee($this->debate->room->name, false);
        $response->assertSee($this->affirmativeUser->name, false);
        $response->assertSee($this->negativeUser->name, false);
    }

    /**
     * Test: recordsページでディベートデータが正しく注入される
     * Requirements: 4.1, 4.2, 4.3, 4.4
     */
    public function test_debate_data_is_correctly_injected_on_records_page(): void
    {
        $response = $this->actingAs($this->affirmativeUser)
            ->get(route('records.show', $this->debate));

        $response->assertStatus(200);

        // window.debateData の存在を確認
        $response->assertSee('window.debateData', false);

        // 基本的なディベート情報の注入を確認
        $response->assertSee($this->debate->room->topic, false);
        $response->assertSee($this->debate->room->name, false);
    }

    /**
     * Test: 評価データが正しく注入される
     * Requirements: 4.3
     */
    public function test_evaluation_data_is_correctly_injected(): void
    {
        $response = $this->actingAs($this->affirmativeUser)
            ->get(route('debate.result', $this->debate));

        $response->assertStatus(200);

        // 評価データの注入を確認
        $response->assertSee('Test analysis content', false);
        $response->assertSee('Test reason content', false);
        $response->assertSee('Test feedback for affirmative', false);
        $response->assertSee('Test feedback for negative', false);
    }

    /**
     * Test: メッセージデータが正しく注入される
     * Requirements: 4.4
     */
    public function test_message_data_is_correctly_injected(): void
    {
        $response = $this->actingAs($this->affirmativeUser)
            ->get(route('debate.result', $this->debate));

        $response->assertStatus(200);

        // メッセージデータの注入を確認
        $response->assertSee('Test message from affirmative', false);
        $response->assertSee('Test message from negative', false);
    }

    /**
     * Test: resultページへのアクセス権限チェック - 参加者のみアクセス可能
     * Requirements: 1.4, 5.2
     */
    public function test_result_page_access_is_restricted_to_participants(): void
    {
        // 肯定側ユーザーはアクセス可能
        $response = $this->actingAs($this->affirmativeUser)
            ->get(route('debate.result', $this->debate));
        $response->assertStatus(200);

        // 否定側ユーザーはアクセス可能
        $response = $this->actingAs($this->negativeUser)
            ->get(route('debate.result', $this->debate));
        $response->assertStatus(200);

        // 参加していないユーザーはリダイレクトされる
        $response = $this->actingAs($this->otherUser)
            ->get(route('debate.result', $this->debate));
        $response->assertRedirect();
    }

    /**
     * Test: recordsページへのアクセス権限チェック - 参加者のみアクセス可能
     * Requirements: 1.4, 5.2
     */
    public function test_records_page_access_is_restricted_to_participants(): void
    {
        // 肯定側ユーザーはアクセス可能
        $response = $this->actingAs($this->affirmativeUser)
            ->get(route('records.show', $this->debate));
        $response->assertStatus(200);

        // 否定側ユーザーはアクセス可能
        $response = $this->actingAs($this->negativeUser)
            ->get(route('records.show', $this->debate));
        $response->assertStatus(200);

        // 参加していないユーザーはリダイレクトされる
        $response = $this->actingAs($this->otherUser)
            ->get(route('records.show', $this->debate));
        $response->assertRedirect();
    }

    /**
     * Test: 評価データがない場合でもページが正常に表示される
     * Requirements: 4.3
     */
    public function test_page_displays_correctly_without_evaluation_data(): void
    {
        // 評価データを削除
        $this->debate->evaluations()->delete();

        $response = $this->actingAs($this->affirmativeUser)
            ->get(route('debate.result', $this->debate));

        $response->assertStatus(200);

        // コピーボタンは表示される
        $response->assertSee(__('records.copy_report'), false);
    }

    /**
     * Test: AIユーザーとのディベートでデータが正しく注入される
     * Requirements: 4.2
     */
    public function test_ai_debate_data_is_correctly_injected(): void
    {
        // AIとのディベートを作成
        $aiDebate = Debate::factory()->create([
            'room_id' => $this->room->id,
            'affirmative_user_id' => $this->affirmativeUser->id,
            'negative_user_id' => $this->aiUser->id,
        ]);

        DebateEvaluation::factory()->create([
            'debate_id' => $aiDebate->id,
            'is_analyzable' => true,
            'winner' => 'negative',
        ]);

        $response = $this->actingAs($this->affirmativeUser)
            ->get(route('debate.result', $aiDebate));

        $response->assertStatus(200);

        // AIユーザーの情報が含まれることを確認
        $response->assertSee($this->aiUser->name, false);
        $response->assertSee(__('ai_debate.ai_label'), false);
    }

    /**
     * Test: 備考がない場合でもページが正常に表示される
     * Requirements: 4.1
     */
    public function test_page_displays_correctly_without_remarks(): void
    {
        // 備考なしのルームを作成
        $roomWithoutRemarks = Room::factory()->create([
            'name' => 'Room Without Remarks',
            'topic' => 'Test Topic',
            'remarks' => null,
            'status' => Room::STATUS_FINISHED,
            'created_by' => $this->affirmativeUser->id,
        ]);

        $debateWithoutRemarks = Debate::factory()->create([
            'room_id' => $roomWithoutRemarks->id,
            'affirmative_user_id' => $this->affirmativeUser->id,
            'negative_user_id' => $this->negativeUser->id,
        ]);

        DebateEvaluation::factory()->create([
            'debate_id' => $debateWithoutRemarks->id,
        ]);

        $response = $this->actingAs($this->affirmativeUser)
            ->get(route('debate.result', $debateWithoutRemarks));

        $response->assertStatus(200);

        // コピーボタンは表示される
        $response->assertSee(__('records.copy_report'), false);
    }

    /**
     * Test: ターン情報が正しく渡される
     * Requirements: 4.4
     */
    public function test_turn_information_is_passed_correctly(): void
    {
        $response = $this->actingAs($this->affirmativeUser)
            ->get(route('debate.result', $this->debate));

        $response->assertStatus(200);

        // ターン情報が含まれることを確認（window.debateDataの存在）
        $response->assertSee('window.debateData', false);
    }
}
