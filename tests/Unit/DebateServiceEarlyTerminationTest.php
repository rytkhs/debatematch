<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Debate;
use App\Models\Room;
use App\Models\User;
use App\Services\DebateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use App\Events\EarlyTerminationRequested;
use App\Events\EarlyTerminationAgreed;
use App\Events\EarlyTerminationDeclined;

class DebateServiceEarlyTerminationTest extends TestCase
{
    use RefreshDatabase;

    private DebateService $debateService;
    private Debate $debate;
    private User $affirmativeUser;
    private User $negativeUser;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->debateService = new DebateService();

        // テスト用ユーザーを作成
        $this->affirmativeUser = User::factory()->create();
        $this->negativeUser = User::factory()->create();

        // フリーフォーマットのルームを作成
        $this->room = Room::create([
            'name' => 'Test Room',
            'topic' => 'Test Topic',
            'format_type' => 'free',
            'status' => Room::STATUS_DEBATING,
            'host_user_id' => $this->affirmativeUser->id,
            'custom_format_settings' => [
                '1' => [
                    'speaker' => 'affirmative',
                    'name' => 'suggestion_free_speech',
                    'duration' => 300,
                    'is_prep_time' => false,
                    'is_questions' => false
                ],
                '2' => [
                    'speaker' => 'negative',
                    'name' => 'suggestion_free_speech',
                    'duration' => 300,
                    'is_prep_time' => false,
                    'is_questions' => false
                ]
            ]
        ]);

        // ディベートを作成
        $this->debate = Debate::create([
            'room_id' => $this->room->id,
            'affirmative_user_id' => $this->affirmativeUser->id,
            'negative_user_id' => $this->negativeUser->id,
            'current_turn' => 1
        ]);
    }

    public function test_isFreeFormat_returns_true_for_free_format()
    {
        $result = $this->debateService->isFreeFormat($this->debate);
        $this->assertTrue($result);
    }

    public function test_isFreeFormat_returns_false_for_non_free_format()
    {
        $this->room->update(['format_type' => 'nafa']);
        $this->debate->refresh();

        $result = $this->debateService->isFreeFormat($this->debate);
        $this->assertFalse($result);
    }

    public function test_requestEarlyTermination_success()
    {
        Event::fake();
        Cache::shouldReceive('has')->once()->andReturn(false);
        Cache::shouldReceive('put')->once()->andReturn(true);

        $result = $this->debateService->requestEarlyTermination($this->debate, $this->affirmativeUser->id);

        $this->assertTrue($result);
        Event::assertDispatched(EarlyTerminationRequested::class);
    }

    public function test_requestEarlyTermination_fails_for_non_participant()
    {
        $nonParticipant = User::factory()->create();

        $result = $this->debateService->requestEarlyTermination($this->debate, $nonParticipant->id);

        $this->assertFalse($result);
    }

    public function test_requestEarlyTermination_fails_for_non_free_format()
    {
        $this->room->update(['format_type' => 'nafa']);
        $this->debate->refresh();

        $result = $this->debateService->requestEarlyTermination($this->debate, $this->affirmativeUser->id);

        $this->assertFalse($result);
    }

    public function test_requestEarlyTermination_fails_when_already_requested()
    {
        Cache::shouldReceive('has')->once()->andReturn(true);

        $result = $this->debateService->requestEarlyTermination($this->debate, $this->affirmativeUser->id);

        $this->assertFalse($result);
    }

    public function test_respondToEarlyTermination_agree_success()
    {
        Event::fake();

        // 早期終了提案が存在する状態をモック
        Cache::shouldReceive('get')->once()->andReturn([
            'requested_by' => $this->affirmativeUser->id,
            'status' => 'requested',
            'timestamp' => now()->toISOString()
        ]);
        Cache::shouldReceive('forget')->once()->andReturn(true);

        $result = $this->debateService->respondToEarlyTermination($this->debate, $this->negativeUser->id, true);

        $this->assertTrue($result);
        Event::assertDispatched(EarlyTerminationAgreed::class);
    }

    public function test_respondToEarlyTermination_decline_success()
    {
        Event::fake();

        // 早期終了提案が存在する状態をモック
        Cache::shouldReceive('get')->once()->andReturn([
            'requested_by' => $this->affirmativeUser->id,
            'status' => 'requested',
            'timestamp' => now()->toISOString()
        ]);
        Cache::shouldReceive('forget')->once()->andReturn(true);

        $result = $this->debateService->respondToEarlyTermination($this->debate, $this->negativeUser->id, false);

        $this->assertTrue($result);
        Event::assertDispatched(EarlyTerminationDeclined::class);
    }

    public function test_respondToEarlyTermination_fails_when_no_request()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);

        $result = $this->debateService->respondToEarlyTermination($this->debate, $this->negativeUser->id, true);

        $this->assertFalse($result);
    }

    public function test_respondToEarlyTermination_fails_when_requester_responds()
    {
        Cache::shouldReceive('get')->once()->andReturn([
            'requested_by' => $this->affirmativeUser->id,
            'status' => 'requested',
            'timestamp' => now()->toISOString()
        ]);

        $result = $this->debateService->respondToEarlyTermination($this->debate, $this->affirmativeUser->id, true);

        $this->assertFalse($result);
    }

    public function test_getEarlyTerminationStatus_returns_none_when_no_cache()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);

        $result = $this->debateService->getEarlyTerminationStatus($this->debate);

        $this->assertEquals(['status' => 'none'], $result);
    }

    public function test_getEarlyTerminationStatus_returns_cached_data()
    {
        $cachedData = [
            'status' => 'requested',
            'requested_by' => $this->affirmativeUser->id,
            'timestamp' => now()->toISOString()
        ];

        Cache::shouldReceive('get')->once()->andReturn($cachedData);

        $result = $this->debateService->getEarlyTerminationStatus($this->debate);

        $this->assertEquals($cachedData, $result);
    }

    public function test_getCacheKey_returns_correct_format()
    {
        $reflection = new \ReflectionClass($this->debateService);
        $method = $reflection->getMethod('getCacheKey');
        $method->setAccessible(true);

        $result = $method->invoke($this->debateService, $this->debate->id);

        $this->assertEquals("early_termination_request_{$this->debate->id}", $result);
    }
}
