<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(Contact::getValidTypes()),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'subject' => fake()->sentence(),
            'message' => fake()->paragraphs(3, true),
            'status' => 'new',
            'language' => fake()->randomElement(['ja', 'en']),
            'user_id' => null,
            'admin_notes' => null,
            'replied_at' => null,
        ];
    }

    /**
     * Create a contact from a logged-in user.
     */
    public function fromUser(User $user = null): static
    {
        $user = $user ?? User::factory()->create();

        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    /**
     * Create an anonymous contact (guest user).
     */
    public function anonymous(): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => null,
        ]);
    }

    /**
     * Create a bug report contact.
     */
    public function bugReport(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => Contact::TYPE_BUG_REPORT,
            'subject' => 'Bug Report: ' . fake()->sentence(),
            'message' => 'I encountered a bug when ' . fake()->paragraph(),
        ]);
    }

    /**
     * Create a feature request contact.
     */
    public function featureRequest(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => Contact::TYPE_FEATURE_REQUEST,
            'subject' => 'Feature Request: ' . fake()->sentence(),
            'message' => 'I would like to request a new feature: ' . fake()->paragraph(),
        ]);
    }

    /**
     * Create a general question contact.
     */
    public function generalQuestion(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => Contact::TYPE_GENERAL_QUESTION,
            'subject' => 'Question: ' . fake()->sentence(),
            'message' => 'I have a question about ' . fake()->paragraph(),
        ]);
    }

    /**
     * Create an account issues contact.
     */
    public function accountIssues(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => Contact::TYPE_ACCOUNT_ISSUES,
            'subject' => 'Account Issue: ' . fake()->sentence(),
            'message' => 'I am having trouble with my account: ' . fake()->paragraph(),
        ]);
    }

    /**
     * Create an other type contact.
     */
    public function other(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => Contact::TYPE_OTHER,
            'subject' => fake()->sentence(),
            'message' => fake()->paragraph(),
        ]);
    }

    /**
     * Create a contact with new status.
     */
    public function newStatus(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'new',
            'admin_notes' => null,
            'replied_at' => null,
        ]);
    }

    /**
     * Create a contact with in_progress status.
     */
    public function inProgress(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'in_progress',
            'admin_notes' => 'Working on this issue.',
        ]);
    }

    /**
     * Create a contact with replied status.
     */
    public function replied(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'replied',
            'admin_notes' => 'Replied to the user.',
            'replied_at' => now()->subDays(fake()->numberBetween(1, 7)),
        ]);
    }

    /**
     * Create a contact with resolved status.
     */
    public function resolved(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'resolved',
            'admin_notes' => 'Issue has been resolved.',
            'replied_at' => now()->subDays(fake()->numberBetween(1, 14)),
        ]);
    }

    /**
     * Create a contact with closed status.
     */
    public function closed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'closed',
            'admin_notes' => 'Closed without further action needed.',
            'replied_at' => now()->subDays(fake()->numberBetween(7, 30)),
        ]);
    }

    /**
     * Create a contact in Japanese.
     */
    public function japanese(): static
    {
        return $this->state(fn(array $attributes) => [
            'language' => 'ja',
            'subject' => 'お問い合わせ：' . fake()->realText(50),
            'message' => 'いつもお世話になっております。' . fake()->realText(200),
        ]);
    }

    /**
     * Create a contact in English.
     */
    public function english(): static
    {
        return $this->state(fn(array $attributes) => [
            'language' => 'en',
        ]);
    }

    /**
     * Create a contact with admin notes.
     */
    public function withAdminNotes(string $notes = null): static
    {
        return $this->state(fn(array $attributes) => [
            'admin_notes' => $notes ?? fake()->paragraph(),
        ]);
    }

    /**
     * Create an urgent contact (high priority keywords).
     */
    public function urgent(): static
    {
        return $this->state(fn(array $attributes) => [
            'subject' => 'URGENT: ' . fake()->sentence(),
            'message' => 'This is urgent! ' . fake()->paragraph(),
            'type' => Contact::TYPE_BUG_REPORT,
        ]);
    }

    /**
     * Create a spam-like contact.
     */
    public function spam(): static
    {
        return $this->state(fn(array $attributes) => [
            'subject' => 'CHECK THIS OUT!!!',
            'message' => 'Hello! I have a great offer for you... ' . fake()->url(),
            'type' => Contact::TYPE_OTHER,
        ]);
    }
}
