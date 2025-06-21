<?php

namespace Tests\Unit\Services\AI;

use Tests\TestCase;
use App\Services\AI\AIDebateCreationService;
use App\Models\Room;
use App\Models\User;
use App\Models\Debate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;

class AIDebateCreationServiceTest extends TestCase
{
    use RefreshDatabase;

    private AIDebateCreationService $aiDebateService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aiDebateService = app(AIDebateCreationService::class);
        Log::spy();
    }

    #[Test]
    public function can_create_ai_debate_successfully()
    {
        $creator = User::factory()->create();
        $aiUser = User::factory()->create();
        config(['app.ai_user_id' => $aiUser->id]);

        $validatedData = [
            'topic' => 'AI Ethics',
            'side' => 'affirmative',
            'language' => 'japanese',
            'format_type' => 'format_name_nada_high',
        ];

        $debate = $this->aiDebateService->createAIDebate($validatedData, $creator);

        $this->assertInstanceOf(Debate::class, $debate);
        $this->assertEquals('AI Ethics', $debate->room->topic);
        $this->assertTrue($debate->room->is_ai_debate);
        $this->assertEquals(Room::STATUS_DEBATING, $debate->room->status);
        $this->assertEquals($creator->id, $debate->affirmative_user_id);
        $this->assertEquals($aiUser->id, $debate->negative_user_id);
    }

    #[Test]
    public function can_create_ai_debate_with_negative_side()
    {
        $creator = User::factory()->create();
        $aiUser = User::factory()->create();
        config(['app.ai_user_id' => $aiUser->id]);

        $validatedData = [
            'topic' => 'Climate Change',
            'side' => 'negative',
            'language' => 'english',
            'format_type' => 'format_name_nsda_ld',
        ];

        $debate = $this->aiDebateService->createAIDebate($validatedData, $creator);

        $this->assertEquals($aiUser->id, $debate->affirmative_user_id);
        $this->assertEquals($creator->id, $debate->negative_user_id);
    }

    #[Test]
    public function throws_exception_when_ai_user_not_found()
    {
        $creator = User::factory()->create();
        config(['app.ai_user_id' => 999]);

        $validatedData = [
            'topic' => 'Test Topic',
            'side' => 'affirmative',
            'language' => 'japanese',
            'format_type' => 'format_name_nada_high',
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('AI User not found');

        $this->aiDebateService->createAIDebate($validatedData, $creator);
    }

    #[Test]
    public function can_exit_ai_debate_successfully()
    {
        $user = User::factory()->create();
        $aiUser = User::factory()->create();
        config(['app.ai_user_id' => $aiUser->id]);

        $room = Room::factory()->create([
            'is_ai_debate' => true,
            'status' => Room::STATUS_DEBATING,
            'created_by' => $user->id,
        ]);

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $user->id,
            'negative_user_id' => $aiUser->id,
            'turn_end_time' => now(),
        ]);

        $this->aiDebateService->exitAIDebate($debate, $user);

        $room->refresh();
        $debate->refresh();

        $this->assertEquals(Room::STATUS_DELETED, $room->status);
        $this->assertNull($debate->turn_end_time);
    }

    #[Test]
    public function throws_exception_when_exiting_non_participant()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $aiUser = User::factory()->create();

        $room = Room::factory()->create(['is_ai_debate' => true]);
        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $otherUser->id,
            'negative_user_id' => $aiUser->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You are not a participant in this debate');

        $this->aiDebateService->exitAIDebate($debate, $user);
    }

    #[Test]
    public function throws_exception_when_exiting_non_ai_debate()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $room = Room::factory()->create(['is_ai_debate' => false]);
        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $user->id,
            'negative_user_id' => $otherUser->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('This is not an AI debate');

        $this->aiDebateService->exitAIDebate($debate, $user);
    }
}
