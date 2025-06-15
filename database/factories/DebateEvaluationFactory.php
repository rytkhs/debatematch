<?php

namespace Database\Factories;

use App\Models\DebateEvaluation;
use App\Models\Debate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DebateEvaluation>
 */
class DebateEvaluationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'debate_id' => Debate::factory(),
            'is_analyzable' => true,
            'winner' => fake()->randomElement([
                DebateEvaluation::WINNER_AFFIRMATIVE,
                DebateEvaluation::WINNER_NEGATIVE
            ]),
            'analysis' => fake()->paragraphs(3, true),
            'reason' => fake()->paragraph(),
            'feedback_for_affirmative' => fake()->paragraph(),
            'feedback_for_negative' => fake()->paragraph(),
        ];
    }

    /**
     * Create an evaluation for a specific debate.
     */
    public function forDebate(Debate $debate): static
    {
        return $this->state(fn(array $attributes) => [
            'debate_id' => $debate->id,
        ]);
    }

    /**
     * Create an evaluation with affirmative winner.
     */
    public function affirmativeWins(): static
    {
        return $this->state(fn(array $attributes) => [
            'winner' => DebateEvaluation::WINNER_AFFIRMATIVE,
            'reason' => 'The affirmative side presented stronger arguments and evidence.',
            'feedback_for_affirmative' => 'Excellent performance. Strong logical flow and convincing evidence.',
            'feedback_for_negative' => 'Good effort, but the rebuttals could have been stronger.',
        ]);
    }

    /**
     * Create an evaluation with negative winner.
     */
    public function negativeWins(): static
    {
        return $this->state(fn(array $attributes) => [
            'winner' => DebateEvaluation::WINNER_NEGATIVE,
            'reason' => 'The negative side effectively countered the affirmative arguments.',
            'feedback_for_affirmative' => 'Good initial arguments, but failed to address the negative rebuttals effectively.',
            'feedback_for_negative' => 'Excellent counter-arguments and strong defense.',
        ]);
    }

    /**
     * Create an analyzable evaluation.
     */
    public function analyzable(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_analyzable' => true,
        ]);
    }

    /**
     * Create a non-analyzable evaluation.
     */
    public function notAnalyzable(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_analyzable' => false,
            'analysis' => null,
            'reason' => 'The debate was terminated early and cannot be properly analyzed.',
            'feedback_for_affirmative' => null,
            'feedback_for_negative' => null,
        ]);
    }

    /**
     * Create a detailed evaluation with comprehensive analysis.
     */
    public function detailed(): static
    {
        return $this->state(fn(array $attributes) => [
            'analysis' => fake()->paragraphs(5, true) . "\n\nStrengths:\n" . fake()->paragraph() . "\n\nWeaknesses:\n" . fake()->paragraph(),
            'reason' => fake()->paragraphs(2, true),
            'feedback_for_affirmative' => fake()->paragraphs(2, true),
            'feedback_for_negative' => fake()->paragraphs(2, true),
        ]);
    }

    /**
     * Create a brief evaluation with minimal analysis.
     */
    public function brief(): static
    {
        return $this->state(fn(array $attributes) => [
            'analysis' => fake()->sentence(),
            'reason' => fake()->sentence(),
            'feedback_for_affirmative' => fake()->sentence(),
            'feedback_for_negative' => fake()->sentence(),
        ]);
    }

    /**
     * Create an evaluation for an AI debate.
     */
    public function aiDebate(): static
    {
        return $this->state(fn(array $attributes) => [
            'analysis' => 'AI vs Human debate analysis: ' . fake()->paragraph(),
            'reason' => fake()->randomElement([
                'The human participant showed better emotional appeal.',
                'The AI provided more structured and logical arguments.',
                'Both sides presented valid points, but the winner had better evidence.'
            ]),
            'feedback_for_affirmative' => 'Consider the AI\'s systematic approach to argument structure.',
            'feedback_for_negative' => 'The AI could benefit from more human-like emotional intelligence.',
        ]);
    }

    /**
     * Create an evaluation for a close debate.
     */
    public function closeDecision(): static
    {
        return $this->state(fn(array $attributes) => [
            'reason' => 'This was a very close debate. ' . fake()->paragraph(),
            'analysis' => 'Both sides presented strong arguments. ' . fake()->paragraphs(2, true),
            'feedback_for_affirmative' => 'Very close performance. ' . fake()->paragraph(),
            'feedback_for_negative' => 'Very close performance. ' . fake()->paragraph(),
        ]);
    }

    /**
     * Create an evaluation for a decisive victory.
     */
    public function decisiveVictory(): static
    {
        return $this->state(fn(array $attributes) => [
            'reason' => 'This was a decisive victory. ' . fake()->paragraph(),
            'analysis' => 'One side clearly dominated the debate. ' . fake()->paragraphs(2, true),
        ]);
    }

    /**
     * Create an evaluation with technical feedback.
     */
    public function withTechnicalFeedback(): static
    {
        return $this->state(fn(array $attributes) => [
            'feedback_for_affirmative' => 'Technical aspects: ' . fake()->paragraph() . ' Structure: ' . fake()->paragraph(),
            'feedback_for_negative' => 'Technical aspects: ' . fake()->paragraph() . ' Structure: ' . fake()->paragraph(),
            'analysis' => 'Technical analysis: ' . fake()->paragraphs(3, true),
        ]);
    }

    /**
     * Create an evaluation with positive feedback for both sides.
     */
    public function positiveFeedback(): static
    {
        return $this->state(fn(array $attributes) => [
            'feedback_for_affirmative' => 'Excellent work! ' . fake()->paragraph(),
            'feedback_for_negative' => 'Great performance! ' . fake()->paragraph(),
            'analysis' => 'Both participants showed high level of skill. ' . fake()->paragraph(),
        ]);
    }

    /**
     * Create an evaluation with constructive criticism.
     */
    public function constructiveCriticism(): static
    {
        return $this->state(fn(array $attributes) => [
            'feedback_for_affirmative' => 'Areas for improvement: ' . fake()->paragraph(),
            'feedback_for_negative' => 'Areas for improvement: ' . fake()->paragraph(),
            'analysis' => 'Both sides have room for growth. ' . fake()->paragraph(),
        ]);
    }
}
