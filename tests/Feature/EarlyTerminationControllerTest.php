<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Debate;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use App\Events\EarlyTerminationRequested;
use App\Events\EarlyTerminationAgreed;
use App\Events\EarlyTerminationDeclined;

class EarlyTerminationControllerTest extends TestCase
{
    use RefreshDatabase;

    private Debate $debate;
    private User $affirmativeUser;
    private User $negativeUser;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_request_early_termination_success()
    {
        Event::fake();
        Cache::shouldReceive('has')->once()->andReturn(false);
        Cache::shouldReceive('put')->once()->andReturn(true);

        $response = $this->actingAs($this->affirmativeUser)
            ->postJson("/debates/{$this->debate->id}/early-termination/request");

        $response->assertStatus(200)
            ->assertJson([
                'message' => __('messages.early_termination_requested')
            ]);

        Event::assertDispatched(EarlyTerminationRequested::class);
    }

    public function test_request_early_termination_unauthorized()
    {
        $response = $this->postJson("/debates/{$this->debate->id}/early-termination/request");

        $response->assertStatus(401);
    }

    public function test_request_early_termination_non_participant()
    {
        /** @var User $nonParticipant */
        $nonParticipant = User::factory()->create();

        $response = $this->actingAs($nonParticipant)
            ->postJson("/debates/{$this->debate->id}/early-termination/request");

        $response->assertStatus(400)
            ->assertJson([
                'error' => __('messages.early_termination_request_failed')
            ]);
    }

    public function test_request_early_termination_non_free_format()
    {
        $this->room->update(['format_type' => 'nafa']);

        $response = $this->actingAs($this->affirmativeUser)
            ->postJson("/debates/{$this->debate->id}/early-termination/request");

        $response->assertStatus(400)
            ->assertJson([
                'error' => __('messages.early_termination_request_failed')
            ]);
    }

    public function test_respond_early_termination_agree_success()
    {
        Event::fake();
        Cache::shouldReceive('get')->once()->andReturn([
            'requested_by' => $this->affirmativeUser->id,
            'status' => 'requested',
            'timestamp' => now()->toISOString()
        ]);
        Cache::shouldReceive('forget')->once()->andReturn(true);

        $response = $this->actingAs($this->negativeUser)
            ->postJson("/debates/{$this->debate->id}/early-termination/respond", [
                'agree' => true
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => __('messages.early_termination_agreed')
            ]);

        Event::assertDispatched(EarlyTerminationAgreed::class);
    }

    public function test_respond_early_termination_decline_success()
    {
        Event::fake();
        Cache::shouldReceive('get')->once()->andReturn([
            'requested_by' => $this->affirmativeUser->id,
            'status' => 'requested',
            'timestamp' => now()->toISOString()
        ]);
        Cache::shouldReceive('forget')->once()->andReturn(true);

        $response = $this->actingAs($this->negativeUser)
            ->postJson("/debates/{$this->debate->id}/early-termination/respond", [
                'agree' => false
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => __('messages.early_termination_declined')
            ]);

        Event::assertDispatched(EarlyTerminationDeclined::class);
    }

    public function test_respond_early_termination_validation_error()
    {
        $response = $this->actingAs($this->negativeUser)
            ->postJson("/debates/{$this->debate->id}/early-termination/respond", [
                // agreeパラメータなし
            ]);

        $response->assertStatus(422);
    }

    public function test_respond_early_termination_no_request()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);

        $response = $this->actingAs($this->negativeUser)
            ->postJson("/debates/{$this->debate->id}/early-termination/respond", [
                'agree' => true
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => __('messages.early_termination_response_failed')
            ]);
    }

    public function test_respond_early_termination_requester_cannot_respond()
    {
        Cache::shouldReceive('get')->once()->andReturn([
            'requested_by' => $this->affirmativeUser->id,
            'status' => 'requested',
            'timestamp' => now()->toISOString()
        ]);

        $response = $this->actingAs($this->affirmativeUser)
            ->postJson("/debates/{$this->debate->id}/early-termination/respond", [
                'agree' => true
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => __('messages.early_termination_response_failed')
            ]);
    }

    public function test_get_early_termination_status_none()
    {
        Cache::shouldReceive('get')->once()->andReturn(null);

        $response = $this->actingAs($this->affirmativeUser)
            ->getJson("/debates/{$this->debate->id}/early-termination/status");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'none'
            ]);
    }

    public function test_get_early_termination_status_requested()
    {
        $cachedData = [
            'status' => 'requested',
            'requested_by' => $this->affirmativeUser->id,
            'timestamp' => now()->toISOString()
        ];

        Cache::shouldReceive('get')->once()->andReturn($cachedData);

        $response = $this->actingAs($this->negativeUser)
            ->getJson("/debates/{$this->debate->id}/early-termination/status");

        $response->assertStatus(200)
            ->assertJson($cachedData);
    }

    public function test_get_early_termination_status_unauthorized()
    {
        $response = $this->getJson("/debates/{$this->debate->id}/early-termination/status");

        $response->assertStatus(401);
    }

    public function test_debate_not_found()
    {
        $response = $this->actingAs($this->affirmativeUser)
            ->postJson("/debates/99999/early-termination/request");

        $response->assertStatus(404);
    }
}
