<?php

namespace Database\Factories;

use App\Models\ConnectionLog;
use App\Models\User;
use App\Services\ConnectionManager;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ConnectionLog>
 */
class ConnectionLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ConnectionLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $connectedAt = $this->faker->dateTimeBetween('-1 week', 'now');
        $contextTypes = ['room', 'debate', 'admin'];
        $statuses = ['connected', 'disconnected', 'temporarily_disconnected'];

        return [
            'user_id' => User::factory(),
            'context_type' => $this->faker->randomElement($contextTypes),
            'context_id' => $this->faker->numberBetween(1, 100),
            'status' => $this->faker->randomElement($statuses),
            'connected_at' => $connectedAt,
            'disconnected_at' => $this->faker->optional(0.3)->dateTimeBetween($connectedAt, 'now'),
            'reconnected_at' => $this->faker->optional(0.2)->dateTimeBetween($connectedAt, 'now'),
            'metadata' => [
                'client_info' => $this->faker->userAgent(),
                'ip_address' => $this->faker->ipv4(),
                'connection_type' => $this->faker->randomElement(['initial', 'reconnection', 'refresh'])
            ]
        ];
    }

    /**
     * 接続中状態のログ
     */
    public function connected(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'connected',
                'disconnected_at' => null,
                'reconnected_at' => null,
            ];
        });
    }

    /**
     * 切断状態のログ
     */
    public function disconnected(): static
    {
        return $this->state(function (array $attributes) {
            $connectedAt = $attributes['connected_at'] ?? now()->subHours(2);
            return [
                'status' => 'disconnected',
                'disconnected_at' => $this->faker->dateTimeBetween($connectedAt, 'now'),
                'reconnected_at' => null,
            ];
        });
    }

    /**
     * 一時的に切断状態のログ
     */
    public function temporarilyDisconnected(): static
    {
        return $this->state(function (array $attributes) {
            $connectedAt = $attributes['connected_at'] ?? now()->subHours(1);
            return [
                'status' => 'temporarily_disconnected',
                'disconnected_at' => $this->faker->dateTimeBetween($connectedAt, 'now'),
                'reconnected_at' => $this->faker->optional(0.7)->dateTimeBetween($connectedAt, 'now'),
            ];
        });
    }

    /**
     * 特定のユーザーのログ
     */
    public function forUser(User $user): static
    {
        return $this->state([
            'user_id' => $user->id,
        ]);
    }

    /**
     * 特定のコンテキストのログ
     */
    public function forContext(string $contextType, int $contextId): static
    {
        return $this->state([
            'context_type' => $contextType,
            'context_id' => $contextId,
        ]);
    }

    /**
     * ルームコンテキストのログ
     */
    public function roomContext(int $roomId = null): static
    {
        return $this->state([
            'context_type' => 'room',
            'context_id' => $roomId ?? $this->faker->numberBetween(1, 50),
        ]);
    }

    /**
     * ディベートコンテキストのログ
     */
    public function debateContext(int $debateId = null): static
    {
        return $this->state([
            'context_type' => 'debate',
            'context_id' => $debateId ?? $this->faker->numberBetween(1, 50),
        ]);
    }

    /**
     * 管理者コンテキストのログ
     */
    public function adminContext(): static
    {
        return $this->state([
            'context_type' => 'admin',
            'context_id' => 1,
        ]);
    }

    /**
     * 初回接続のログ
     */
    public function initialConnection(): static
    {
        return $this->state([
            'status' => 'connected',
            'disconnected_at' => null,
            'reconnected_at' => null,
            'metadata' => [
                'client_info' => $this->faker->userAgent(),
                'ip_address' => $this->faker->ipv4(),
                'connection_type' => 'initial'
            ]
        ]);
    }

    /**
     * 再接続のログ
     */
    public function reconnection(): static
    {
        return $this->state(function (array $attributes) {
            $connectedAt = $attributes['connected_at'] ?? now()->subMinutes(30);
            return [
                'status' => 'connected',
                'reconnected_at' => $this->faker->dateTimeBetween($connectedAt, 'now'),
                'metadata' => [
                    'client_info' => $this->faker->userAgent(),
                    'ip_address' => $this->faker->ipv4(),
                    'connection_type' => 'reconnection'
                ]
            ];
        });
    }

    /**
     * 長時間接続のログ
     */
    public function longConnection(): static
    {
        return $this->state([
            'connected_at' => now()->subHours($this->faker->numberBetween(4, 12)),
            'status' => 'connected',
            'disconnected_at' => null,
            'reconnected_at' => null,
        ]);
    }

    /**
     * 短時間接続のログ
     */
    public function shortConnection(): static
    {
        return $this->state(function (array $attributes) {
            $connectedAt = now()->subMinutes($this->faker->numberBetween(1, 30));
            return [
                'connected_at' => $connectedAt,
                'status' => 'disconnected',
                'disconnected_at' => $connectedAt->addMinutes($this->faker->numberBetween(1, 15)),
                'reconnected_at' => null,
            ];
        });
    }

    /**
     * 頻繁な切断のログ
     */
    public function frequentDisconnection(): static
    {
        return $this->state([
            'status' => 'temporarily_disconnected',
            'metadata' => [
                'client_info' => $this->faker->userAgent(),
                'ip_address' => $this->faker->ipv4(),
                'connection_type' => 'unstable',
                'disconnection_reason' => $this->faker->randomElement([
                    'network_timeout',
                    'client_refresh',
                    'server_restart',
                    'connection_lost'
                ])
            ]
        ]);
    }

    /**
     * モバイル接続のログ
     */
    public function mobileConnection(): static
    {
        return $this->state([
            'metadata' => [
                'client_info' => $this->faker->randomElement([
                    'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15',
                    'Mozilla/5.0 (Android 11; Mobile; rv:68.0) Gecko/68.0 Firefox/88.0',
                    'Mozilla/5.0 (Linux; Android 11; SM-G991B) AppleWebKit/537.36'
                ]),
                'ip_address' => $this->faker->ipv4(),
                'connection_type' => 'mobile',
                'device_type' => 'mobile'
            ]
        ]);
    }

    /**
     * デスクトップ接続のログ
     */
    public function desktopConnection(): static
    {
        return $this->state([
            'metadata' => [
                'client_info' => $this->faker->randomElement([
                    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36'
                ]),
                'ip_address' => $this->faker->ipv4(),
                'connection_type' => 'desktop',
                'device_type' => 'desktop'
            ]
        ]);
    }

    /**
     * 特定の期間内のログ
     */
    public function inPeriod(string $start, string $end): static
    {
        return $this->state([
            'created_at' => $this->faker->dateTimeBetween($start, $end),
            'connected_at' => $this->faker->dateTimeBetween($start, $end),
        ]);
    }

    /**
     * 最近のログ
     */
    public function recent(): static
    {
        return $this->state([
            'created_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'connected_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * 古いログ
     */
    public function old(): static
    {
        return $this->state([
            'created_at' => $this->faker->dateTimeBetween('-1 month', '-1 week'),
            'connected_at' => $this->faker->dateTimeBetween('-1 month', '-1 week'),
        ]);
    }
}
