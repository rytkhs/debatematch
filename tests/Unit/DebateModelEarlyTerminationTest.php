<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Debate;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DebateModelEarlyTerminationTest extends TestCase
{
    use RefreshDatabase;

    private Debate $debate;
    private User $affirmativeUser;
    private User $negativeUser;
    private User $nonParticipant;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用ユーザーを作成
        $this->affirmativeUser = User::factory()->create();
        $this->negativeUser = User::factory()->create();
        $this->nonParticipant = User::factory()->create();

        // ルームを作成
        $room = Room::create([
            'name' => 'Test Room',
            'topic' => 'Test Topic',
            'format_type' => 'free',
            'status' => Room::STATUS_DEBATING,
            'host_user_id' => $this->affirmativeUser->id,
        ]);

        // ディベートを作成
        $this->debate = Debate::create([
            'room_id' => $room->id,
            'affirmative_user_id' => $this->affirmativeUser->id,
            'negative_user_id' => $this->negativeUser->id,
            'current_turn' => 1
        ]);
    }

    public function test_canRequestEarlyTermination_returns_true_for_affirmative_user()
    {
        $result = $this->debate->canRequestEarlyTermination($this->affirmativeUser->id);
        $this->assertTrue($result);
    }

    public function test_canRequestEarlyTermination_returns_true_for_negative_user()
    {
        $result = $this->debate->canRequestEarlyTermination($this->negativeUser->id);
        $this->assertTrue($result);
    }

    public function test_canRequestEarlyTermination_returns_false_for_non_participant()
    {
        $result = $this->debate->canRequestEarlyTermination($this->nonParticipant->id);
        $this->assertFalse($result);
    }

    public function test_canRespondToEarlyTermination_returns_true_for_affirmative_user()
    {
        $result = $this->debate->canRespondToEarlyTermination($this->affirmativeUser->id);
        $this->assertTrue($result);
    }

    public function test_canRespondToEarlyTermination_returns_true_for_negative_user()
    {
        $result = $this->debate->canRespondToEarlyTermination($this->negativeUser->id);
        $this->assertTrue($result);
    }

    public function test_canRespondToEarlyTermination_returns_false_for_non_participant()
    {
        $result = $this->debate->canRespondToEarlyTermination($this->nonParticipant->id);
        $this->assertFalse($result);
    }

    public function test_canRequestEarlyTermination_handles_null_user_id()
    {
        $result = $this->debate->canRequestEarlyTermination(99999);
        $this->assertFalse($result);
    }

    public function test_canRespondToEarlyTermination_handles_null_user_id()
    {
        $result = $this->debate->canRespondToEarlyTermination(99999);
        $this->assertFalse($result);
    }

    public function test_canRequestEarlyTermination_handles_zero_user_id()
    {
        $result = $this->debate->canRequestEarlyTermination(0);
        $this->assertFalse($result);
    }

    public function test_canRespondToEarlyTermination_handles_zero_user_id()
    {
        $result = $this->debate->canRespondToEarlyTermination(0);
        $this->assertFalse($result);
    }

    public function test_canRequestEarlyTermination_handles_negative_user_id()
    {
        $result = $this->debate->canRequestEarlyTermination(-1);
        $this->assertFalse($result);
    }

    public function test_canRespondToEarlyTermination_handles_negative_user_id()
    {
        $result = $this->debate->canRespondToEarlyTermination(-1);
        $this->assertFalse($result);
    }
}
