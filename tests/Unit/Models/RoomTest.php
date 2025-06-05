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

    /**
     * TODO-012: Room ステータス管理テスト
     */

    /** @test */
    public function test_update_status()
    {
        $room = Room::factory()->create(['status' => Room::STATUS_WAITING]);

        $room->updateStatus(Room::STATUS_READY);

        $this->assertEquals(Room::STATUS_READY, $room->fresh()->status);
        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'status' => Room::STATUS_READY
        ]);
    }

    /** @test */
    public function test_update_status_valid_transitions()
    {
        // Test waiting to ready
        $room = Room::factory()->waiting()->create();
        $room->updateStatus(Room::STATUS_READY);
        $this->assertEquals(Room::STATUS_READY, $room->fresh()->status);

        // Test ready to debating
        $room = Room::factory()->ready()->create();
        $room->updateStatus(Room::STATUS_DEBATING);
        $this->assertEquals(Room::STATUS_DEBATING, $room->fresh()->status);

        // Test debating to finished
        $room = Room::factory()->debating()->create();
        $room->updateStatus(Room::STATUS_FINISHED);
        $this->assertEquals(Room::STATUS_FINISHED, $room->fresh()->status);

        // Test to terminated from any status
        $room = Room::factory()->waiting()->create();
        $room->updateStatus(Room::STATUS_TERMINATED);
        $this->assertEquals(Room::STATUS_TERMINATED, $room->fresh()->status);
    }

    /** @test */
    public function test_update_status_with_all_available_statuses()
    {
        foreach (Room::AVAILABLE_STATUSES as $status) {
            $room = Room::factory()->create();
            $room->updateStatus($status);
            $this->assertEquals($status, $room->fresh()->status);
        }
    }

    /** @test */
    public function test_status_constants_in_database()
    {
        $room = Room::factory()->waiting()->create();
        $this->assertEquals(Room::STATUS_WAITING, $room->status);

        $room = Room::factory()->ready()->create();
        $this->assertEquals(Room::STATUS_READY, $room->status);

        $room = Room::factory()->debating()->create();
        $this->assertEquals(Room::STATUS_DEBATING, $room->status);

        $room = Room::factory()->finished()->create();
        $this->assertEquals(Room::STATUS_FINISHED, $room->status);
    }

    /**
     * TODO-013: Room ディベートフォーマットテスト
     */

    /** @test */
    public function test_get_debate_format_standard_format()
    {
        $room = Room::factory()->create(['format_type' => 'format_name_jda']);

        $format = $room->getDebateFormat();

        $this->assertIsArray($format);
        $this->assertNotEmpty($format);

        // JDAフォーマットは20ターンであることを確認
        $this->assertCount(20, $format);

        // 最初のターンの構造を確認
        $firstTurn = $format[1];
        $this->assertArrayHasKey('speaker', $firstTurn);
        $this->assertArrayHasKey('name', $firstTurn);
        $this->assertArrayHasKey('duration', $firstTurn);
        $this->assertEquals('affirmative', $firstTurn['speaker']);
    }

    /** @test */
    public function test_get_debate_format_custom_format()
    {
        $customSettings = [
            ['name' => 'Opening Statement', 'time_limit' => 300, 'side' => 'affirmative'],
            ['name' => 'Rebuttal', 'time_limit' => 240, 'side' => 'negative'],
            ['name' => 'Closing Statement', 'time_limit' => 180, 'side' => 'affirmative']
        ];

        $room = Room::factory()->create([
            'format_type' => 'custom',
            'custom_format_settings' => $customSettings
        ]);

        $format = $room->getDebateFormat();

        $this->assertIsArray($format);
        $this->assertCount(3, $format);
        $this->assertEquals('Opening Statement', $format[0]['name']);
        $this->assertEquals('Rebuttal', $format[1]['name']);
        $this->assertEquals('Closing Statement', $format[2]['name']);
    }

    /** @test */
    public function test_get_debate_format_free_format()
    {
        $freeSettings = [];

        $room = Room::factory()->create([
            'format_type' => 'free',
            'custom_format_settings' => $freeSettings
        ]);

        $format = $room->getDebateFormat();

        $this->assertIsArray($format);
        $this->assertEmpty($format);
    }

    /** @test */
    public function test_get_debate_format_with_translation()
    {
        // Set locale to Japanese for testing translation
        App::setLocale('ja');

        $room = Room::factory()->create(['format_type' => 'format_name_jda']);
        $format = $room->getDebateFormat();

        // Test that turn names are translated
        $this->assertNotEmpty($format);

        // The name should be translated from the config
        $firstTurn = $format[1];
        $this->assertNotEquals('suggestion_1st_constructive', $firstTurn['name']);
    }

    /** @test */
    public function test_get_debate_format_custom_with_translation_keys()
    {
        App::setLocale('ja');

        $customSettings = [
            ['name' => 'suggestion_constructive', 'time_limit' => 300, 'side' => 'affirmative'],
            ['name' => 'Custom Turn', 'time_limit' => 240, 'side' => 'negative']
        ];

        $room = Room::factory()->create([
            'format_type' => 'custom',
            'custom_format_settings' => $customSettings
        ]);

        $format = $room->getDebateFormat();

        // Translation key should be translated
        $this->assertNotEquals('suggestion_constructive', $format[0]['name']);

        // Non-translation key should remain as is
        $this->assertEquals('Custom Turn', $format[1]['name']);
    }

    /** @test */
    public function test_get_debate_format_different_formats()
    {
        $formats = ['format_name_nsda_policy', 'format_name_nsda_ld', 'format_name_jda', 'format_name_coda'];

        foreach ($formats as $formatType) {
            $room = Room::factory()->create(['format_type' => $formatType]);
            $format = $room->getDebateFormat();

            $this->assertIsArray($format);
            $this->assertNotEmpty($format);
        }
    }

    /** @test */
    public function test_get_debate_format_empty_custom_settings()
    {
        $room = Room::factory()->create([
            'format_type' => 'custom',
            'custom_format_settings' => null
        ]);

        $format = $room->getDebateFormat();

        // Should fall back to config format (empty array for custom with null settings)
        $this->assertIsArray($format);
    }

    /** @test */
    public function test_get_format_name()
    {
        // Test standard format
        $room = Room::factory()->create(['format_type' => 'format_name_jda']);
        App::setLocale('ja');
        $formatName = $room->getFormatName();
        $this->assertNotEmpty($formatName);

        // Test custom format
        $room = Room::factory()->create(['format_type' => 'custom']);
        $formatName = $room->getFormatName();
        $this->assertNotEmpty($formatName);

        // Test free format
        $room = Room::factory()->create(['format_type' => 'free']);
        $formatName = $room->getFormatName();
        $this->assertNotEmpty($formatName);
    }

    /** @test */
    public function test_is_free_format()
    {
        $freeRoom = Room::factory()->create(['format_type' => 'free']);
        $this->assertTrue($freeRoom->isFreeFormat());

        $standardRoom = Room::factory()->create(['format_type' => 'format_name_jda']);
        $this->assertFalse($standardRoom->isFreeFormat());

        $customRoom = Room::factory()->create(['format_type' => 'custom']);
        $this->assertFalse($customRoom->isFreeFormat());
    }

    /**
     * Relationship tests
     */

    /** @test */
    public function test_users_relationship()
    {
        $this->assertBelongsToMany('users', User::class);
    }

    /** @test */
    public function test_creator_relationship()
    {
        $this->assertBelongsTo('creator', User::class);
    }

    /** @test */
    public function test_debate_relationship()
    {
        $this->assertHasOne('debate', Debate::class);
    }

    /** @test */
    public function test_users_relationship_with_pivot()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        $room->users()->attach($user->id, ['side' => 'affirmative']);

        $this->assertTrue($room->users->contains($user));
        $this->assertEquals('affirmative', $room->users->first()->pivot->side);
    }

    /** @test */
    public function test_creator_relationship_with_soft_deleted_user()
    {
        $creator = User::factory()->create();
        $room = Room::factory()->create(['created_by' => $creator->id]);

        // Soft delete the creator
        $creator->delete();

        // Should still be able to access creator through withTrashed
        $this->assertNotNull($room->creator);
        $this->assertEquals($creator->id, $room->creator->id);
    }

    /**
     * Factory state tests
     */

    /** @test */
    public function test_factory_states()
    {
        $this->assertFactoryStates([
            'waiting',
            'ready',
            'debating',
            'finished'
        ]);
    }

    /** @test */
    public function test_factory_ai_debate()
    {
        $room = Room::factory()->aiDebate()->create();
        $this->assertTrue($room->is_ai_debate);
    }

    /** @test */
    public function test_factory_custom_format()
    {
        $room = Room::factory()->customFormat()->create();
        $this->assertEquals('custom', $room->format_type);
        $this->assertNotEmpty($room->custom_format_settings);
    }

    /** @test */
    public function test_factory_free_format()
    {
        $room = Room::factory()->freeFormat()->create();
        $this->assertEquals('free', $room->format_type);
        $this->assertEmpty($room->custom_format_settings);
    }

    /** @test */
    public function test_factory_with_evidence()
    {
        $room = Room::factory()->withEvidence()->create();
        $this->assertTrue($room->evidence_allowed);
    }

    /** @test */
    public function test_factory_without_evidence()
    {
        $room = Room::factory()->withoutEvidence()->create();
        $this->assertFalse($room->evidence_allowed);
    }

    /** @test */
    public function test_factory_language_methods()
    {
        $japaneseRoom = Room::factory()->japanese()->create();
        $this->assertEquals('ja', $japaneseRoom->language);

        $englishRoom = Room::factory()->english()->create();
        $this->assertEquals('en', $englishRoom->language);
    }

    /**
     * RoomUser pivot tests
     */

    /** @test */
    public function test_room_user_constants()
    {
        $this->assertEquals('affirmative', RoomUser::SIDE_AFFIRMATIVE);
        $this->assertEquals('negative', RoomUser::SIDE_NEGATIVE);
    }

    /** @test */
    public function test_room_user_through_room_users_relationship()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        // Attach user to room using the relationship
        $room->users()->attach($user->id, ['side' => RoomUser::SIDE_AFFIRMATIVE]);

        // Get the pivot record
        $roomUser = $room->users->first()->pivot;

        $this->assertEquals($room->id, $roomUser->room_id);
        $this->assertEquals($user->id, $roomUser->user_id);
        $this->assertEquals(RoomUser::SIDE_AFFIRMATIVE, $roomUser->side);
    }

    /** @test */
    public function test_room_user_relationships_through_pivot()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        // Attach user to room
        $room->users()->attach($user->id, ['side' => RoomUser::SIDE_NEGATIVE]);

        // Find the RoomUser instance
        $roomUser = RoomUser::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->first();

        // Test room relationship
        $this->assertInstanceOf(Room::class, $roomUser->room);
        $this->assertEquals($room->id, $roomUser->room->id);

        // Test user relationship
        $this->assertInstanceOf(User::class, $roomUser->user);
        $this->assertEquals($user->id, $roomUser->user->id);
    }

    /** @test */
    public function test_room_user_is_creator()
    {
        $creator = User::factory()->create();
        $participant = User::factory()->create();
        $room = Room::factory()->create(['created_by' => $creator->id]);

        // Attach creator to room
        $room->users()->attach($creator->id, ['side' => RoomUser::SIDE_AFFIRMATIVE]);

        // Attach participant to room
        $room->users()->attach($participant->id, ['side' => RoomUser::SIDE_NEGATIVE]);

        // Get the RoomUser instances
        $creatorRoomUser = RoomUser::where('room_id', $room->id)
            ->where('user_id', $creator->id)
            ->first();

        $participantRoomUser = RoomUser::where('room_id', $room->id)
            ->where('user_id', $participant->id)
            ->first();

        $this->assertTrue($creatorRoomUser->isCreator());
        $this->assertFalse($participantRoomUser->isCreator());
    }

    /** @test */
    public function test_room_user_different_sides()
    {
        $room = Room::factory()->create();
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();

        // Attach users with different sides
        $room->users()->attach($affirmativeUser->id, ['side' => RoomUser::SIDE_AFFIRMATIVE]);
        $room->users()->attach($negativeUser->id, ['side' => RoomUser::SIDE_NEGATIVE]);

        $affirmativeRoomUser = RoomUser::where('room_id', $room->id)
            ->where('user_id', $affirmativeUser->id)
            ->first();

        $negativeRoomUser = RoomUser::where('room_id', $room->id)
            ->where('user_id', $negativeUser->id)
            ->first();

        $this->assertEquals(RoomUser::SIDE_AFFIRMATIVE, $affirmativeRoomUser->side);
        $this->assertEquals(RoomUser::SIDE_NEGATIVE, $negativeRoomUser->side);
    }

    /** @test */
    public function test_room_user_with_soft_deleted_user()
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        // Attach user to room
        $room->users()->attach($user->id, ['side' => RoomUser::SIDE_AFFIRMATIVE]);

        // Get the RoomUser instance
        $roomUser = RoomUser::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->first();

        // Soft delete the user
        $user->delete();

        // Should still be able to access user through withTrashed
        $this->assertNotNull($roomUser->fresh()->user);
        $this->assertEquals($user->id, $roomUser->fresh()->user->id);
        $this->assertNotNull($roomUser->fresh()->user->deleted_at);
    }
}
