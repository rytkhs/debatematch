<?php

namespace Tests\Unit\Services\Room;

use Tests\TestCase;
use App\Services\Room\RoomCreationService;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class RoomCreationServiceTest extends TestCase
{
    use RefreshDatabase;

    private RoomCreationService $roomCreationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->roomCreationService = app(RoomCreationService::class);
    }

    #[Test]
    public function creates_room_with_basic_format()
    {
        $creator = User::factory()->create();
        $validatedData = [
            'name' => 'Test Room',
            'topic' => 'Climate Change',
            'side' => 'affirmative',
            'remarks' => 'Test remarks',
            'language' => 'japanese',
            'format_type' => 'nada_high',
            'evidence_allowed' => true,
        ];

        $room = $this->roomCreationService->createRoom($validatedData, $creator);

        $this->assertInstanceOf(Room::class, $room);
        $this->assertEquals('Test Room', $room->name);
        $this->assertEquals('Climate Change', $room->topic);
        $this->assertEquals('Test remarks', $room->remarks);
        $this->assertEquals('japanese', $room->language);
        $this->assertEquals('nada_high', $room->format_type);
        $this->assertTrue($room->evidence_allowed);
        $this->assertEquals(Room::STATUS_WAITING, $room->status);
        $this->assertEquals($creator->id, $room->created_by);

        // 作成者がルームに参加していることを確認
        $this->assertTrue($room->users->contains($creator));
        $this->assertEquals('affirmative', $room->users->first()->pivot->side);
    }

    #[Test]
    public function creates_room_with_custom_format()
    {
        $creator = User::factory()->create();
        $validatedData = [
            'name' => 'Custom Room',
            'topic' => 'AI Ethics',
            'side' => 'negative',
            'language' => 'english',
            'format_type' => 'custom',
            'evidence_allowed' => false,
            'turns' => [
                ['name' => 'Opening', 'duration' => 5, 'speaker' => 'affirmative', 'is_prep_time' => false, 'is_questions' => false],
                ['name' => 'Response', 'duration' => 4, 'speaker' => 'negative', 'is_prep_time' => false, 'is_questions' => false]
            ]
        ];

        $room = $this->roomCreationService->createRoom($validatedData, $creator);

        $this->assertEquals('custom', $room->format_type);
        $this->assertNotNull($room->custom_format_settings);
        $this->assertFalse($room->evidence_allowed);
        $this->assertEquals('negative', $room->users->first()->pivot->side);
    }

    #[Test]
    public function creates_room_with_free_format()
    {
        $creator = User::factory()->create();
        $validatedData = [
            'name' => 'Free Room',
            'topic' => 'Technology',
            'side' => 'affirmative',
            'language' => 'japanese',
            'format_type' => 'free',
            'evidence_allowed' => true,
            'turn_duration' => 3,
            'max_turns' => 4
        ];

        $room = $this->roomCreationService->createRoom($validatedData, $creator);

        $this->assertEquals('free', $room->format_type);
        $this->assertNotNull($room->custom_format_settings);
        $this->assertCount(4, $room->custom_format_settings);
    }

    #[Test]
    public function creates_room_without_optional_fields()
    {
        $creator = User::factory()->create();
        $validatedData = [
            'name' => 'Simple Room',
            'topic' => 'Simple Topic',
            'side' => 'affirmative',
            'language' => 'english',
            'format_type' => 'nsda_policy',
            'evidence_allowed' => false,
            // remarks は省略
        ];

        $room = $this->roomCreationService->createRoom($validatedData, $creator);

        $this->assertNull($room->remarks);
        $this->assertNull($room->custom_format_settings);
    }
}
