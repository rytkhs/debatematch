<?php

namespace Tests\Unit\Models;

use App\Models\Room;
use App\Models\User;
use App\Models\Debate;
use App\Models\RoomUser;
use Tests\Traits\CreatesRooms;
use Tests\Traits\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\App;

class RoomTest extends BaseModelTest
{
    use RefreshDatabase, CreatesRooms, CreatesUsers;

    protected string $modelClass = Room::class;

    /**
     * TODO-011: Room モデル基本機能テスト
     */

    /** @test */
    public function test_fillable_attributes()
    {
        $expectedFillable = [
            'name',
            'topic',
            'remarks',
            'status',
            'created_by',
            'language',
            'format_type',
            'custom_format_settings',
            'evidence_allowed',
            'is_ai_debate'
        ];

        $this->assertModelBasics($expectedFillable);
    }

    /** @test */
    public function test_casts()
    {
        $expectedCasts = [
            'custom_format_settings' => 'array',
            'evidence_allowed' => 'boolean',
        ];

        $actualCasts = $this->model->getCasts();

        // Check if expected casts are present in actual casts
        foreach ($expectedCasts as $key => $value) {
            $this->assertEquals($value, $actualCasts[$key], "Cast for {$key} should be {$value}");
        }

        // Check that the casts are working correctly
        $this->assertArrayHasKey('custom_format_settings', $actualCasts);
        $this->assertArrayHasKey('evidence_allowed', $actualCasts);
    }

    /** @test */
    public function test_status_constants()
    {
        $this->assertEquals('waiting', Room::STATUS_WAITING);
        $this->assertEquals('ready', Room::STATUS_READY);
        $this->assertEquals('debating', Room::STATUS_DEBATING);
        $this->assertEquals('finished', Room::STATUS_FINISHED);
        $this->assertEquals('deleted', Room::STATUS_DELETED);
        $this->assertEquals('terminated', Room::STATUS_TERMINATED);
    }

    /** @test */
    public function test_available_statuses_constant()
    {
        $expectedStatuses = [
            Room::STATUS_WAITING,
            Room::STATUS_READY,
            Room::STATUS_DEBATING,
            Room::STATUS_FINISHED,
            Room::STATUS_DELETED,
            Room::STATUS_TERMINATED
        ];

        $this->assertEquals($expectedStatuses, Room::AVAILABLE_STATUSES);
    }

    /** @test */
    public function test_factory_creation()
    {
        $this->assertFactoryCreation();
    }

    /** @test */
    public function test_basic_attributes()
    {
        $room = Room::factory()->create([
            'name' => 'Test Room',
            'topic' => 'Test Topic',
            'remarks' => 'Test Remarks',
            'language' => 'ja',
            'format_type' => 'format_name_jda',
            'evidence_allowed' => true,
            'is_ai_debate' => false
        ]);

        $this->assertEquals('Test Room', $room->name);
        $this->assertEquals('Test Topic', $room->topic);
        $this->assertEquals('Test Remarks', $room->remarks);
        $this->assertEquals('ja', $room->language);
        $this->assertEquals('format_name_jda', $room->format_type);
        $this->assertTrue($room->evidence_allowed);
        $this->assertFalse($room->is_ai_debate);
    }

    /** @test */
    public function test_custom_format_settings_cast()
    {
        $formatSettings = [
            ['name' => 'Opening', 'time_limit' => 300, 'side' => 'affirmative'],
            ['name' => 'Rebuttal', 'time_limit' => 240, 'side' => 'negative']
        ];

        $room = Room::factory()->create([
            'format_type' => 'custom',
            'custom_format_settings' => $formatSettings
        ]);

        $this->assertIsArray($room->custom_format_settings);
        $this->assertEquals($formatSettings, $room->custom_format_settings);
    }

    /** @test */
    public function test_evidence_allowed_cast()
    {
        $room = Room::factory()->create(['evidence_allowed' => 1]);
        $this->assertIsBool($room->evidence_allowed);
        $this->assertTrue($room->evidence_allowed);

        $room = Room::factory()->create(['evidence_allowed' => 0]);
        $this->assertIsBool($room->evidence_allowed);
        $this->assertFalse($room->evidence_allowed);
    }

    /** @test */
    public function test_soft_deletes()
    {
        $this->assertSoftDeletes();
    }
}
