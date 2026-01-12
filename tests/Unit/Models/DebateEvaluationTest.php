<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\Attributes\Test;
use App\Models\DebateEvaluation;
use App\Models\Debate;
use App\Models\User;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesDebates;
use Tests\Traits\CreatesUsers;

class DebateEvaluationTest extends TestCase
{
    use RefreshDatabase, CreatesDebates, CreatesUsers;

    #[Test]
    public function test_fillable_attributes()
    {
        $expectedFillable = [
            'debate_id',
            'is_analyzable',
            'winner',
            'analysis',
            'reason',
            'feedback_for_affirmative',
            'feedback_for_negative',
        ];

        $evaluation = new DebateEvaluation();
        $this->assertEquals($expectedFillable, $evaluation->getFillable());
    }

    #[Test]
    public function test_uses_traits()
    {
        $evaluation = new DebateEvaluation();

        $this->assertContains('Illuminate\Database\Eloquent\Factories\HasFactory', class_uses($evaluation));
        $this->assertContains('Illuminate\Database\Eloquent\SoftDeletes', class_uses($evaluation));
    }

    #[Test]
    public function test_winner_constants()
    {
        $this->assertEquals('affirmative', DebateEvaluation::WINNER_AFFIRMATIVE);
        $this->assertEquals('negative', DebateEvaluation::WINNER_NEGATIVE);
    }

    #[Test]
    public function test_factory_creation()
    {
        $evaluation = DebateEvaluation::factory()->create();

        $this->assertInstanceOf(DebateEvaluation::class, $evaluation);
        $this->assertDatabaseHas('debate_evaluations', ['id' => $evaluation->id]);
    }

    #[Test]
    public function test_basic_attributes()
    {
        $debate = Debate::factory()->create();

        $evaluation = DebateEvaluation::factory()->create([
            'debate_id' => $debate->id,
            'is_analyzable' => true,
            'winner' => DebateEvaluation::WINNER_AFFIRMATIVE,
            'analysis' => 'Comprehensive analysis of the debate',
            'reason' => 'Affirmative presented stronger arguments',
            'feedback_for_affirmative' => 'Excellent logical flow',
            'feedback_for_negative' => 'Good effort but could improve rebuttals',
        ]);

        $this->assertEquals($debate->id, $evaluation->debate_id);
        $this->assertTrue($evaluation->is_analyzable);
        $this->assertEquals(DebateEvaluation::WINNER_AFFIRMATIVE, $evaluation->winner);
        $this->assertEquals('Comprehensive analysis of the debate', $evaluation->analysis);
        $this->assertEquals('Affirmative presented stronger arguments', $evaluation->reason);
        $this->assertEquals('Excellent logical flow', $evaluation->feedback_for_affirmative);
        $this->assertEquals('Good effort but could improve rebuttals', $evaluation->feedback_for_negative);
    }

    #[Test]
    public function test_soft_deletes()
    {
        $evaluation = DebateEvaluation::factory()->create();
        $evaluationId = $evaluation->id;

        $evaluation->delete();

        // Should be soft deleted
        $this->assertDatabaseHas('debate_evaluations', [
            'id' => $evaluationId,
        ]);
        $this->assertNotNull($evaluation->fresh()->deleted_at);

        // Should not be found in normal queries
        $this->assertNull(DebateEvaluation::find($evaluationId));

        // Should be found with trashed
        $this->assertNotNull(DebateEvaluation::withTrashed()->find($evaluationId));
    }

    /**
     * Relationship tests
     */

    #[Test]
    public function test_debate_relationship()
    {
        $debate = Debate::factory()->create();
        $evaluation = DebateEvaluation::factory()->create(['debate_id' => $debate->id]);

        $this->assertInstanceOf(Debate::class, $evaluation->debate);
        $this->assertEquals($debate->id, $evaluation->debate->id);
    }

    #[Test]
    public function test_belongs_to_debate_cascade()
    {
        $debate = Debate::factory()->create();
        $evaluation = DebateEvaluation::factory()->create(['debate_id' => $debate->id]);

        $debateId = $debate->id;
        $evaluationId = $evaluation->id;

        // Delete debate (soft delete)
        $debate->delete();

        // Evaluation should still exist but debate should be soft deleted
        $this->assertNotNull(DebateEvaluation::find($evaluationId));
        $this->assertNotNull(Debate::withTrashed()->find($debateId));
        $this->assertNull(Debate::find($debateId));
    }

    /**
     * Factory state tests
     */

    #[Test]
    public function test_factory_for_debate()
    {
        $debate = Debate::factory()->create();
        $evaluation = DebateEvaluation::factory()->forDebate($debate)->create();

        $this->assertEquals($debate->id, $evaluation->debate_id);
    }

    #[Test]
    public function test_factory_affirmative_wins()
    {
        $evaluation = DebateEvaluation::factory()->affirmativeWins()->create();

        $this->assertEquals(DebateEvaluation::WINNER_AFFIRMATIVE, $evaluation->winner);
        $this->assertStringContainsString('affirmative', strtolower($evaluation->reason));
        $this->assertNotNull($evaluation->feedback_for_affirmative);
        $this->assertNotNull($evaluation->feedback_for_negative);
    }

    #[Test]
    public function test_factory_negative_wins()
    {
        $evaluation = DebateEvaluation::factory()->negativeWins()->create();

        $this->assertEquals(DebateEvaluation::WINNER_NEGATIVE, $evaluation->winner);
        $this->assertStringContainsString('negative', strtolower($evaluation->reason));
        $this->assertNotNull($evaluation->feedback_for_affirmative);
        $this->assertNotNull($evaluation->feedback_for_negative);
    }

    #[Test]
    public function test_factory_analyzable()
    {
        $evaluation = DebateEvaluation::factory()->analyzable()->create();

        $this->assertTrue($evaluation->is_analyzable);
        $this->assertNotNull($evaluation->analysis);
    }

    #[Test]
    public function test_factory_not_analyzable()
    {
        $evaluation = DebateEvaluation::factory()->notAnalyzable()->create();

        $this->assertFalse($evaluation->is_analyzable);
        $this->assertNull($evaluation->analysis);
        $this->assertNull($evaluation->feedback_for_affirmative);
        $this->assertNull($evaluation->feedback_for_negative);
        $this->assertNotNull($evaluation->reason);
    }

    #[Test]
    public function test_factory_detailed()
    {
        $evaluation = DebateEvaluation::factory()->detailed()->create();

        $this->assertGreaterThan(500, strlen($evaluation->analysis));
        $this->assertGreaterThan(100, strlen($evaluation->reason));
        $this->assertGreaterThan(100, strlen($evaluation->feedback_for_affirmative));
        $this->assertGreaterThan(100, strlen($evaluation->feedback_for_negative));
    }

    #[Test]
    public function test_factory_brief()
    {
        $evaluation = DebateEvaluation::factory()->brief()->create();

        $this->assertLessThan(200, strlen($evaluation->analysis));
        $this->assertLessThan(200, strlen($evaluation->reason));
        $this->assertLessThan(200, strlen($evaluation->feedback_for_affirmative));
        $this->assertLessThan(200, strlen($evaluation->feedback_for_negative));
    }

    #[Test]
    public function test_factory_ai_debate()
    {
        $evaluation = DebateEvaluation::factory()->aiDebate()->create();

        $this->assertStringContainsString('AI', $evaluation->analysis);
        $this->assertStringContainsString('AI', $evaluation->feedback_for_negative);
    }

    #[Test]
    public function test_factory_close_decision()
    {
        $evaluation = DebateEvaluation::factory()->closeDecision()->create();

        $this->assertStringContainsString('close', strtolower($evaluation->reason));
        $this->assertStringContainsString('close', strtolower($evaluation->feedback_for_affirmative));
        $this->assertStringContainsString('close', strtolower($evaluation->feedback_for_negative));
    }

    #[Test]
    public function test_factory_decisive_victory()
    {
        $evaluation = DebateEvaluation::factory()->decisiveVictory()->create();

        $this->assertStringContainsString('decisive', strtolower($evaluation->reason));
        $this->assertStringContainsString('clear', strtolower($evaluation->analysis));
    }

    #[Test]
    public function test_factory_inconclusive()
    {
        $evaluation = DebateEvaluation::factory()->inconclusive()->create();

        $this->assertNull($evaluation->winner);
        $this->assertStringContainsString('inconclusive', strtolower($evaluation->reason));
        $this->assertStringContainsString('could not be properly evaluated', strtolower($evaluation->analysis));
        $this->assertStringContainsString('inconclusive nature', strtolower($evaluation->feedback_for_affirmative));
        $this->assertStringContainsString('inconclusive nature', strtolower($evaluation->feedback_for_negative));
    }

    /**
     * Integration tests
     */

    #[Test]
    public function test_debate_with_evaluation()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $room = Room::factory()->create();

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $user1->id,
            'negative_user_id' => $user2->id,
        ]);

        $evaluation = DebateEvaluation::factory()->create([
            'debate_id' => $debate->id,
            'winner' => DebateEvaluation::WINNER_AFFIRMATIVE,
        ]);

        // Test relationship
        $this->assertEquals($evaluation->id, $debate->debateEvaluation->id);
        $this->assertEquals($debate->id, $evaluation->debate->id);

        // Test winner determination
        $this->assertEquals(DebateEvaluation::WINNER_AFFIRMATIVE, $evaluation->winner);
    }

    #[Test]
    public function test_evaluation_with_null_feedback()
    {
        $evaluation = DebateEvaluation::factory()->create([
            'feedback_for_affirmative' => null,
            'feedback_for_negative' => null,
        ]);

        $this->assertNull($evaluation->feedback_for_affirmative);
        $this->assertNull($evaluation->feedback_for_negative);
        $this->assertNotNull($evaluation->reason);
    }

    #[Test]
    public function test_evaluation_with_long_content()
    {
        $longAnalysis = str_repeat('This is a detailed analysis. ', 100);
        $longReason = str_repeat('This is a comprehensive reason. ', 50);

        $evaluation = DebateEvaluation::factory()->create([
            'analysis' => $longAnalysis,
            'reason' => $longReason,
        ]);

        $this->assertEquals($longAnalysis, $evaluation->analysis);
        $this->assertEquals($longReason, $evaluation->reason);
    }

    #[Test]
    public function test_evaluation_with_special_characters()
    {
        $specialChars = '特殊文字テスト！@#$%^&*()_+-=[]{}|;:\'",.<>?/~`';

        $evaluation = DebateEvaluation::factory()->create([
            'analysis' => $specialChars,
            'reason' => $specialChars,
            'feedback_for_affirmative' => $specialChars,
            'feedback_for_negative' => $specialChars,
        ]);

        $this->assertEquals($specialChars, $evaluation->analysis);
        $this->assertEquals($specialChars, $evaluation->reason);
        $this->assertEquals($specialChars, $evaluation->feedback_for_affirmative);
        $this->assertEquals($specialChars, $evaluation->feedback_for_negative);
    }

    #[Test]
    public function test_evaluation_winner_validation()
    {
        // Test valid winners
        $affirmativeWin = DebateEvaluation::factory()->create(['winner' => DebateEvaluation::WINNER_AFFIRMATIVE]);
        $negativeWin = DebateEvaluation::factory()->create(['winner' => DebateEvaluation::WINNER_NEGATIVE]);

        $this->assertEquals(DebateEvaluation::WINNER_AFFIRMATIVE, $affirmativeWin->winner);
        $this->assertEquals(DebateEvaluation::WINNER_NEGATIVE, $negativeWin->winner);
    }

    #[Test]
    public function test_evaluation_analyzable_scenarios()
    {
        // Analyzable evaluation should have analysis
        $analyzable = DebateEvaluation::factory()->analyzable()->create();
        $this->assertTrue($analyzable->is_analyzable);
        $this->assertNotNull($analyzable->analysis);

        // Non-analyzable evaluation should not have analysis
        $notAnalyzable = DebateEvaluation::factory()->notAnalyzable()->create();
        $this->assertFalse($notAnalyzable->is_analyzable);
        $this->assertNull($notAnalyzable->analysis);
    }

    #[Test]
    public function test_evaluation_complete_feedback_scenario()
    {
        $debate = Debate::factory()->create();
        $evaluation = DebateEvaluation::factory()->detailed()->create([
            'debate_id' => $debate->id,
            'winner' => DebateEvaluation::WINNER_AFFIRMATIVE,
            'is_analyzable' => true,
        ]);

        // Should have all feedback components
        $this->assertNotNull($evaluation->analysis);
        $this->assertNotNull($evaluation->reason);
        $this->assertNotNull($evaluation->feedback_for_affirmative);
        $this->assertNotNull($evaluation->feedback_for_negative);

        // Should be properly linked to debate
        $this->assertEquals($debate->id, $evaluation->debate_id);
        $this->assertEquals($evaluation->id, $debate->debateEvaluation->id);
    }
}
