<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoomStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_allows_transition_from_terminated_to_deleted()
    {
        $room = Room::factory()->create(['status' => Room::STATUS_TERMINATED]);

        $room->updateStatus(Room::STATUS_DELETED);

        $this->assertEquals(Room::STATUS_DELETED, $room->fresh()->status);
    }

    /**
     * @test
     */
    public function it_allows_transition_from_finished_to_deleted()
    {
        $room = Room::factory()->create(['status' => Room::STATUS_FINISHED]);

        $room->updateStatus(Room::STATUS_DELETED);

        $this->assertEquals(Room::STATUS_DELETED, $room->fresh()->status);
    }
}
