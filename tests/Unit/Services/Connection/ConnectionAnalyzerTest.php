<?php

namespace Tests\Unit\Services\Connection;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Services\Connection\ConnectionAnalyzer;
use App\Models\ConnectionLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;
use Mockery;

class ConnectionAnalyzerTest extends TestCase
{
    use RefreshDatabase;

    private ConnectionAnalyzer $analyzer;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyzer = new ConnectionAnalyzer();
        $this->user = User::factory()->create();

        // 設定値をテスト用に設定
        Config::set('connection.analysis.disconnection_threshold', 5);
        Config::set('connection.analysis.analysis_window_hours', 1);
        Config::set('connection.grace_periods.room', 840);
        Config::set('connection.grace_periods.debate', 300);
    }

    #[Test]
    public function getBasicConnectionStatsReturnsAccurateStatistics()
    {
        // 様々な接続ログを作成
        $this->createConnectionLogs();

        $stats = $this->analyzer->getBasicConnectionStats($this->user->id, 24);

        $this->assertEquals(5, $stats['total_connections']);
        $this->assertEquals(2, $stats['disconnections']);
        $this->assertEquals(1, $stats['reconnections']);
        $this->assertEquals(1, $stats['final_disconnections']);
        $this->assertEquals(50.0, $stats['reconnection_rate']); // 1/2 * 100
        $this->assertEquals(24, $stats['analysis_period_hours']);
        $this->assertArrayHasKey('generated_at', $stats);
    }

    #[Test]
    public function getBasicConnectionStatsHandlesEmptyDataProperly()
    {
        $stats = $this->analyzer->getBasicConnectionStats($this->user->id, 24);

        $this->assertEquals(0, $stats['total_connections']);
        $this->assertEquals(0, $stats['disconnections']);
        $this->assertEquals(0, $stats['reconnections']);
        $this->assertEquals(0, $stats['final_disconnections']);
        $this->assertEquals(0, $stats['reconnection_rate']);
    }

    #[Test]
    public function getBasicConnectionStatsUsesCache()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with(Mockery::any(), Mockery::any(), Mockery::any())
            ->andReturnUsing(function () {
                return [
                    'total_connections' => 0,
                    'disconnections' => 0,
                    'reconnections' => 0,
                    'final_disconnections' => 0,
                    'reconnection_rate' => 0,
                    'average_disconnection_duration' => 0,
                    'analysis_period_hours' => 24,
                    'generated_at' => now()->toISOString()
                ];
            });

        $stats = $this->analyzer->getBasicConnectionStats($this->user->id, 24);

        $this->assertEquals(0, $stats['total_connections']);
    }

    #[Test]
    public function analyzeConnectionPatternsDetectsAbnormalPatterns()
    {
        $context = ['type' => 'room', 'id' => 1];

        // 頻繁な切断パターンを作成（閾値5回を超える6回）
        for ($i = 0; $i < 6; $i++) {
            ConnectionLog::create([
                'user_id' => $this->user->id,
                'context_type' => 'room',
                'context_id' => 1,
                'status' => 'temporarily_disconnected',
                'created_at' => now()->subMinutes(30 - ($i * 5)),
                'metadata' => []
            ]);
        }

        $patterns = $this->analyzer->analyzeConnectionPatterns($this->user->id, $context);

        $this->assertEquals(1, $patterns['patterns_detected']);
        $this->assertCount(1, $patterns['anomalous_patterns']);
        $this->assertEquals('frequent_disconnections', $patterns['anomalous_patterns'][0]['pattern_type']);
        $this->assertTrue($patterns['anomalous_patterns'][0]['is_anomalous']);
    }

    #[Test]
    public function analyzeConnectionPatternsDetectsRapidReconnectionPatterns()
    {
        $context = ['type' => 'room', 'id' => 1];

        // 短時間再接続パターンを作成（30秒以内の再接続を4回）
        for ($i = 0; $i < 4; $i++) {
            $disconnectedAt = now()->subMinutes(30 - ($i * 5));
            $reconnectedAt = $disconnectedAt->copy()->addSeconds(20); // 20秒後に再接続

            ConnectionLog::create([
                'user_id' => $this->user->id,
                'context_type' => 'room',
                'context_id' => 1,
                'status' => 'connected',
                'disconnected_at' => $disconnectedAt,
                'reconnected_at' => $reconnectedAt,
                'created_at' => $disconnectedAt,
                'metadata' => []
            ]);
        }

        $patterns = $this->analyzer->analyzeConnectionPatterns($this->user->id, $context);

        $this->assertEquals(1, $patterns['patterns_detected']);
        $rapidPattern = collect($patterns['anomalous_patterns'])
            ->firstWhere('pattern_type', 'rapid_reconnections');
        $this->assertNotNull($rapidPattern);
        $this->assertTrue($rapidPattern['is_anomalous']);
    }

    #[Test]
    public function analyzeConnectionPatternsDetectsProlongedDisconnectionPatterns()
    {
        $context = ['type' => 'room', 'id' => 1];

        // 長時間切断パターンを作成（猶予期間の2倍以上）
        ConnectionLog::create([
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'temporarily_disconnected',
            'disconnected_at' => now()->subSeconds(1800), // 30分前（猶予期間840秒の2倍以上）
            'created_at' => now()->subMinutes(30),
            'metadata' => []
        ]);

        $patterns = $this->analyzer->analyzeConnectionPatterns($this->user->id, $context);

        $this->assertEquals(1, $patterns['patterns_detected']);
        $prolongedPattern = collect($patterns['anomalous_patterns'])
            ->firstWhere('pattern_type', 'prolonged_disconnections');
        $this->assertNotNull($prolongedPattern);
        $this->assertTrue($prolongedPattern['is_anomalous']);
    }

    #[Test]
    public function analyzeConnectionPatternsHandlesNormalPatternsCorrectly()
    {
        $context = ['type' => 'room', 'id' => 1];

        // 正常範囲内の切断ログを作成（4回）
        for ($i = 0; $i < 4; $i++) {
            ConnectionLog::create([
                'user_id' => $this->user->id,
                'context_type' => 'room',
                'context_id' => 1,
                'status' => 'temporarily_disconnected',
                'created_at' => now()->subMinutes(30 - ($i * 5)),
                'metadata' => []
            ]);
        }

        $patterns = $this->analyzer->analyzeConnectionPatterns($this->user->id, $context);

        $this->assertEquals(0, $patterns['patterns_detected']);
        $this->assertEmpty($patterns['anomalous_patterns']);
    }

    #[Test]
    public function calculateConnectionQualityScoreCalculatesAppropriateScore()
    {
        $context = ['type' => 'room', 'id' => 1];

        // 良好な接続パターンを作成
        $this->createGoodConnectionPattern();

        $score = $this->analyzer->calculateConnectionQualityScore($this->user->id, $context);

        $this->assertGreaterThan(70, $score['quality_score']);
        $this->assertContains($score['quality_level'], ['good', 'excellent']);
        $this->assertArrayHasKey('score_breakdown', $score);
        $this->assertArrayHasKey('stats', $score);
        $this->assertArrayHasKey('anomalies', $score);
    }

    #[Test]
    public function calculateConnectionQualityScoreEvaluatesPoorConnectionsProperly()
    {
        $context = ['type' => 'room', 'id' => 1];

        // 悪い接続パターンを作成
        $this->createPoorConnectionPattern();

        $score = $this->analyzer->calculateConnectionQualityScore($this->user->id, $context);

        $this->assertLessThan(50, $score['quality_score']);
        $this->assertContains($score['quality_level'], ['poor', 'critical', 'fair']);
    }

    #[Test]
    public function calculateSeverityReturnsAppropriateLevel()
    {
        $severity = $this->invokePrivateMethod($this->analyzer, 'calculateSeverity', [15, 5]);
        $this->assertEquals('critical', $severity);

        $severity = $this->invokePrivateMethod($this->analyzer, 'calculateSeverity', [10, 5]);
        $this->assertEquals('high', $severity);

        $severity = $this->invokePrivateMethod($this->analyzer, 'calculateSeverity', [5, 5]);
        $this->assertEquals('medium', $severity);

        $severity = $this->invokePrivateMethod($this->analyzer, 'calculateSeverity', [3, 5]);
        $this->assertEquals('low', $severity);
    }

    #[Test]
    public function getQualityLevelReturnsAppropriateLevel()
    {
        $level = $this->invokePrivateMethod($this->analyzer, 'getQualityLevel', [95]);
        $this->assertEquals('excellent', $level);

        $level = $this->invokePrivateMethod($this->analyzer, 'getQualityLevel', [80]);
        $this->assertEquals('good', $level);

        $level = $this->invokePrivateMethod($this->analyzer, 'getQualityLevel', [60]);
        $this->assertEquals('fair', $level);

        $level = $this->invokePrivateMethod($this->analyzer, 'getQualityLevel', [40]);
        $this->assertEquals('poor', $level);

        $level = $this->invokePrivateMethod($this->analyzer, 'getQualityLevel', [20]);
        $this->assertEquals('critical', $level);
    }

    #[Test]
    public function analyzeFrequentDisconnectionsPerformsAccurateDetection()
    {
        $context = ['type' => 'room', 'id' => 1];

        // 閾値ちょうどの切断を作成
        for ($i = 0; $i < 5; $i++) {
            ConnectionLog::create([
                'user_id' => $this->user->id,
                'context_type' => 'room',
                'context_id' => 1,
                'status' => 'temporarily_disconnected',
                'created_at' => now()->subMinutes(30 - ($i * 5)),
                'metadata' => []
            ]);
        }

        $result = $this->invokePrivateMethod($this->analyzer, 'analyzeFrequentDisconnections', [$this->user->id, $context, 1]);

        $this->assertTrue($result['is_anomalous']);
        $this->assertEquals(5, $result['disconnection_count']);
        $this->assertEquals('medium', $result['severity']);
    }

    #[Test]
    public function analyzeRapidReconnectionsPerformsAccurateDetection()
    {
        $context = ['type' => 'room', 'id' => 1];

        // 短時間再接続を3回作成
        for ($i = 0; $i < 3; $i++) {
            $disconnectedAt = now()->subMinutes(30 - ($i * 5));
            $reconnectedAt = $disconnectedAt->copy()->addSeconds(15); // 15秒後に再接続

            ConnectionLog::create([
                'user_id' => $this->user->id,
                'context_type' => 'room',
                'context_id' => 1,
                'status' => 'connected',
                'disconnected_at' => $disconnectedAt,
                'reconnected_at' => $reconnectedAt,
                'created_at' => $disconnectedAt,
                'metadata' => []
            ]);
        }

        $result = $this->invokePrivateMethod($this->analyzer, 'analyzeRapidReconnections', [$this->user->id, $context, 1]);

        $this->assertTrue($result['is_anomalous']);
        $this->assertEquals(3, $result['rapid_reconnection_count']);
    }

    /**
     * 様々な接続ログを作成
     */
    private function createConnectionLogs(): void
    {
        // 通常接続
        ConnectionLog::create([
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'connected',
            'created_at' => now()->subHours(2),
            'metadata' => []
        ]);

        // 一時切断
        ConnectionLog::create([
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'temporarily_disconnected',
            'disconnected_at' => now()->subHours(1),
            'created_at' => now()->subHours(1),
            'metadata' => []
        ]);

        // 再接続
        ConnectionLog::create([
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'connected',
            'disconnected_at' => now()->subMinutes(90),
            'reconnected_at' => now()->subMinutes(85),
            'created_at' => now()->subMinutes(90),
            'metadata' => []
        ]);

        // もう一つの一時切断
        ConnectionLog::create([
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'temporarily_disconnected',
            'disconnected_at' => now()->subMinutes(30),
            'created_at' => now()->subMinutes(30),
            'metadata' => []
        ]);

        // 最終切断
        ConnectionLog::create([
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'disconnected',
            'created_at' => now()->subMinutes(10),
            'metadata' => []
        ]);
    }

    /**
     * 良好な接続パターンを作成
     */
    private function createGoodConnectionPattern(): void
    {
        // 安定した接続（2回の接続、1回の正常切断）
        ConnectionLog::create([
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'connected',
            'created_at' => now()->subHours(2),
            'metadata' => []
        ]);

        ConnectionLog::create([
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'gracefully_disconnected',
            'created_at' => now()->subHours(1),
            'metadata' => []
        ]);

        ConnectionLog::create([
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'connected',
            'created_at' => now()->subMinutes(30),
            'metadata' => []
        ]);
    }

    /**
     * 悪い接続パターンを作成
     */
    private function createPoorConnectionPattern(): void
    {
        // 頻繁な切断と一部再接続失敗
        for ($i = 0; $i < 8; $i++) {
            ConnectionLog::create([
                'user_id' => $this->user->id,
                'context_type' => 'room',
                'context_id' => 1,
                'status' => 'temporarily_disconnected',
                'created_at' => now()->subMinutes(60 - ($i * 5)),
                'metadata' => []
            ]);
        }

        // 少数の再接続
        for ($i = 0; $i < 2; $i++) {
            ConnectionLog::create([
                'user_id' => $this->user->id,
                'context_type' => 'room',
                'context_id' => 1,
                'status' => 'connected',
                'reconnected_at' => now()->subMinutes(40 - ($i * 10)),
                'created_at' => now()->subMinutes(45 - ($i * 10)),
                'metadata' => []
            ]);
        }
    }

    /**
     * プライベートメソッドを呼び出すヘルパー
     */
    private function invokePrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
