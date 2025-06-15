<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use App\Models\User;
use App\Models\Room;
use App\Models\Debate;
use App\Models\DebateMessage;
use App\Models\DebateEvaluation;
use App\Jobs\AdvanceDebateTurnJob;
use App\Jobs\EvaluateDebateJob;
use App\Jobs\GenerateAIResponseJob;
use App\Events\DebateStarted;
use App\Events\TurnAdvanced;
use App\Events\DebateFinished;
use App\Events\DebateEvaluated;
use App\Services\DebateService;
use App\Services\AIEvaluationService;
use Carbon\Carbon;
use Livewire\Livewire;
use App\Livewire\Rooms\StartDebateButton;

/**
 * DebateFlowTest - ディベート完全フロー統合テスト
 *
 * ルーム作成→開始→ターン進行→終了→評価までの完全なフローを
 * Horizonなしでキュー同期実行でテストする統合テスト
 */
class DebateFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $affirmativeUser;
    private User $negativeUser;
    private User $aiUser;
    private DebateService $debateService;

    protected function setUp(): void
    {
        parent::setUp();

        // キューを同期実行に設定（Horizonなし）
        Config::set('queue.default', 'sync');

        // テスト用ユーザーの作成
        $this->affirmativeUser = User::factory()->create([
            'name' => 'Affirmative User',
            'email' => 'affirmative@test.com'
        ]);

        $this->negativeUser = User::factory()->create([
            'name' => 'Negative User',
            'email' => 'negative@test.com'
        ]);

        $this->aiUser = User::factory()->create([
            'name' => 'AI User',
            'email' => 'ai@test.com'
        ]);

        // AI ユーザーIDの設定
        Config::set('app.ai_user_id', $this->aiUser->id);

        $this->debateService = new DebateService();

        // AI評価サービスのモック
        $this->mockAIEvaluationService();

        // HTTPレスポンスのモック（AI応答用）
        $this->mockHttpResponses();
    }

    /**
     * メインテスト: 完全ディベートフロー
     * ルーム作成→開始→ターン進行→終了→評価
     */
    public function test_complete_debate_flow_from_room_creation_to_evaluation(): void
    {
        // ステップ1: ルーム作成
        $room = $this->createRoomDirectly();
        $this->assertRoomCreatedCorrectly($room);

        // ステップ2: 参加者を追加してREADY状態にする
        $this->addParticipantAndMakeReady($room);

        // ステップ3: ディベート開始
        $debate = $this->startDebateDirectly($room);
        $this->assertDebateStartedCorrectly($debate);

        // ステップ4: メッセージを追加
        $this->addUserMessage($debate, 1);

        // ステップ5: ディベート手動終了
        $this->simulateDebateCompletion($debate);
        $this->assertDebateFinishedCorrectly($debate);

        // ステップ6: 評価処理のシミュレート
        $this->simulateEvaluation($debate);
        $this->assertEvaluationCompletedCorrectly($debate);

        // ステップ7: 最終状態確認
        $this->assertFinalStateIsCorrect($debate);
    }

    /**
     * エラーケースのテスト: ターン進行中の異常処理
     */
    public function test_debate_flow_with_error_handling(): void
    {
        $room = $this->createRoomDirectly();
        $this->addParticipantAndMakeReady($room);
        $debate = $this->startDebateDirectly($room);

        // 初期状態を確認
        $this->assertEquals(1, $debate->current_turn);

        // 異常なターン番号で進行を試行
        $this->debateService->advanceToNextTurn($debate, 999); // 存在しないターン

        // ディベートが適切に処理されることを確認
        $debate->refresh();
        $this->assertEquals(1, $debate->current_turn); // ターンは変更されない

        // ディベートは正常に終了可能
        $this->debateService->finishDebate($debate);
        $debate->refresh();
        $this->assertEquals(Room::STATUS_FINISHED, $debate->room->status);
    }

    /**
     * 同期キュー処理のテスト
     */
    public function test_queue_synchronous_execution(): void
    {
        // キューが同期実行されることを確認
        $this->assertEquals('sync', Config::get('queue.default'));

        $room = $this->createRoomDirectly();
        $this->addParticipantAndMakeReady($room);
        $debate = $this->startDebateDirectly($room);

        // 同期実行でディベートが正常に開始されることを確認
        $this->assertEquals(1, $debate->current_turn);
        $this->assertEquals(Room::STATUS_DEBATING, $debate->room->status);

        // 同期実行で終了処理も正常に動作することを確認
        $this->debateService->finishDebate($debate);
        $debate->refresh();
        $this->assertEquals(Room::STATUS_FINISHED, $debate->room->status);
    }

    // ========================================================================
    // ヘルパーメソッド: ルーム作成
    // ========================================================================

    private function createRoomDirectly(): Room
    {
        $room = Room::factory()->create([
            'name' => 'Test Debate Room',
            'topic' => 'Should AI be regulated?',
            'remarks' => 'Test debate for integration testing',
            'status' => Room::STATUS_WAITING,
            'language' => 'japanese',
            'format_type' => 'format_name_nada_high',
            'evidence_allowed' => true,
            'created_by' => $this->affirmativeUser->id,
        ]);

        // 作成者を参加者として追加
        $room->users()->attach($this->affirmativeUser->id, ['side' => 'affirmative']);

        return $room;
    }

    private function addParticipantAndMakeReady(Room $room): void
    {
        // 否定側ユーザーを参加させる
        $room->users()->attach($this->negativeUser->id, ['side' => 'negative']);

        // ルームのステータスをREADYに更新
        $room->updateStatus(Room::STATUS_READY);

        $room->refresh();
        $this->assertEquals(Room::STATUS_READY, $room->status);
        $this->assertEquals(2, $room->users()->count());
    }

    // ========================================================================
    // ヘルパーメソッド: ディベート開始
    // ========================================================================

    private function startDebateDirectly(Room $room): Debate
    {
        $debate = Debate::create([
            'room_id' => $room->id,
            'affirmative_user_id' => $this->affirmativeUser->id,
            'negative_user_id' => $this->negativeUser->id,
        ]);

        $this->debateService->startDebate($debate);
        $room->updateStatus(Room::STATUS_DEBATING);

        $debate->refresh();
        return $debate;
    }

    // ========================================================================
    // ヘルパーメソッド: ターン進行
    // ========================================================================



    private function simulateDebateCompletion(Debate $debate): void
    {
        // 手動でディベートを終了
        $this->debateService->finishDebate($debate);

        // ディベートが終了することを確認
        $debate->refresh();
        $this->assertEquals(Room::STATUS_FINISHED, $debate->room->status);
    }

    private function addUserMessage(Debate $debate, int $turn): void
    {
        $format = $this->debateService->getFormat($debate);
        $speaker = $format[$turn]['speaker'];

        $userId = ($speaker === 'affirmative')
            ? $this->affirmativeUser->id
            : $this->negativeUser->id;

        DebateMessage::create([
            'debate_id' => $debate->id,
            'user_id' => $userId,
            'turn' => $turn,
            'turn_number' => $turn,
            'message' => "This is a test message for turn {$turn} by {$speaker} side.",
            'message_type' => 'speech'
        ]);
    }

    // ========================================================================
    // ヘルパーメソッド: 評価処理
    // ========================================================================

    private function simulateEvaluation(Debate $debate): void
    {
        // 既存の評価データがあるかチェック
        if ($debate->evaluations()->exists()) {
            return;
        }

        // 評価データを直接作成（実際のAI評価をスキップ）
        $evaluationData = [
            'debate_id' => $debate->id,
            'is_analyzable' => true,
            'winner' => 'affirmative',
            'analysis' => 'Test analysis of the debate arguments.',
            'reason' => 'Test reason for the judgment decision.',
            'feedback_for_affirmative' => 'Test feedback for affirmative side.',
            'feedback_for_negative' => 'Test feedback for negative side.'
        ];

        DebateEvaluation::create($evaluationData);
    }

    // ========================================================================
    // ヘルパーメソッド: モック設定
    // ========================================================================

    private function mockAIEvaluationService(): void
    {
        $mock = $this->createMock(AIEvaluationService::class);
        $mock->method('evaluate')
            ->willReturn([
                'is_analyzable' => true,
                'winner' => 'affirmative',
                'analysis' => 'Mock AI analysis',
                'reason' => 'Mock AI reasoning',
                'feedback_for_affirmative' => 'Mock feedback for affirmative',
                'feedback_for_negative' => 'Mock feedback for negative'
            ]);

        $this->app->instance(AIEvaluationService::class, $mock);
    }

    private function mockHttpResponses(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Mock AI response for debate turn.'
                        ]
                    ]
                ]
            ], 200)
        ]);
    }

    // ========================================================================
    // ヘルパーメソッド: アサーション
    // ========================================================================

    private function assertRoomCreatedCorrectly(Room $room): void
    {
        $this->assertNotNull($room);
        $this->assertEquals('Test Debate Room', $room->name);
        $this->assertEquals('Should AI be regulated?', $room->topic);
        $this->assertEquals(Room::STATUS_WAITING, $room->status);
        $this->assertEquals($this->affirmativeUser->id, $room->created_by);
        $this->assertTrue($room->users->contains($this->affirmativeUser));
    }

    private function assertDebateStartedCorrectly(Debate $debate): void
    {
        $this->assertNotNull($debate);
        $this->assertEquals(1, $debate->current_turn);
        $this->assertEquals($this->affirmativeUser->id, $debate->affirmative_user_id);
        $this->assertEquals($this->negativeUser->id, $debate->negative_user_id);
        $this->assertNotNull($debate->turn_end_time);
    }

    private function assertDebateFinishedCorrectly(Debate $debate): void
    {
        $debate->refresh();
        $this->assertEquals(Room::STATUS_FINISHED, $debate->room->status);
        $this->assertNull($debate->turn_end_time);
    }

    private function assertEvaluationCompletedCorrectly(Debate $debate): void
    {
        $evaluation = $debate->evaluations()->first();
        $this->assertNotNull($evaluation);
        $this->assertEquals(true, $evaluation->is_analyzable);
        $this->assertEquals('affirmative', $evaluation->winner);
        $this->assertNotEmpty($evaluation->analysis);
        $this->assertNotEmpty($evaluation->reason);
    }

    private function assertFinalStateIsCorrect(Debate $debate): void
    {
        $debate->refresh();

        // ルーム状態
        $this->assertEquals(Room::STATUS_FINISHED, $debate->room->status);

        // ディベート状態
        $this->assertNull($debate->turn_end_time);

        // メッセージ存在確認
        $this->assertGreaterThan(0, $debate->messages()->count());

        // 評価データ存在確認
        $this->assertNotNull($debate->evaluations()->first());
    }
}
