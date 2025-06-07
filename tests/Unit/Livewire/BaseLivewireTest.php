<?php

namespace Tests\Unit\Livewire;

use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Tests\Helpers\MockHelpers;
use Tests\Helpers\LivewireTestHelpers;
use Tests\Helpers\LivewireEventTestHelpers;
use App\Models\User;
use App\Models\Room;
use App\Models\Debate;
use Livewire\Features\SupportTesting\Testable;

/**
 * Livewireコンポーネントテスト用のベースクラス
 *
 * 認証、イベント、セッション管理などの共通機能を提供
 * イベントテスト戦略を統合
 */
abstract class BaseLivewireTest extends TestCase
{
    use LivewireTestHelpers, LivewireEventTestHelpers;

    /**
     * テスト実行前の共通設定
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Livewireコンポーネントテスト用の基本設定
        $this->setupLivewireTest();
    }

    /**
     * Livewireテスト用の基本設定
     */
    protected function setupLivewireTest(): void
    {
        // セッション設定
        Session::start();

        // イベント系のMock設定
        MockHelpers::mockLaravelEvents();

        // 基本的なサービスMock
        MockHelpers::mockCache();

        // イベントテスト基盤の初期化
        $this->setupEventTesting();
    }

    /**
     * 認証済みユーザーでLivewireコンポーネントをテスト
     *
     * @param string $component
     * @param array $parameters
     * @param User|null $user
     * @return Testable
     */
    protected function testAsUser(string $component, array $parameters = [], ?User $user = null): Testable
    {
        if (!$user) {
            $user = User::factory()->create();
        }

        return Livewire::actingAs($user)->test($component, $parameters);
    }

    /**
     * 管理者ユーザーでLivewireコンポーネントをテスト
     *
     * @param string $component
     * @param array $parameters
     * @param User|null $admin
     * @return Testable
     */
    protected function testAsAdmin(string $component, array $parameters = [], ?User $admin = null): Testable
    {
        if (!$admin) {
            $admin = User::factory()->admin()->create();
        }

        return Livewire::actingAs($admin)->test($component, $parameters);
    }

    /**
     * ゲストユーザーでLivewireコンポーネントをテスト
     *
     * @param string $component
     * @param array $parameters
     * @param User|null $guest
     * @return Testable
     */
    protected function testAsGuest(string $component, array $parameters = [], ?User $guest = null): Testable
    {
        if (!$guest) {
            $guest = User::factory()->guest()->create();
        }

        return Livewire::actingAs($guest)->test($component, $parameters);
    }

    /**
     * 未認証状態でLivewireコンポーネントをテスト
     *
     * @param string $component
     * @param array $parameters
     * @return Testable
     */
    protected function testAsUnauthenticated(string $component, array $parameters = []): Testable
    {
        return Livewire::test($component, $parameters);
    }

    /**
     * ディベートとルームを持つユーザーでテスト
     *
     * @param string $component
     * @param array $parameters
     * @param string $side 'affirmative' or 'negative'
     * @param bool $isAiDebate
     * @return array ['livewire' => Testable, 'user' => User, 'room' => Room, 'debate' => Debate]
     */
    protected function testWithDebateContext(
        string $component,
        array $parameters = [],
        string $side = 'affirmative',
        bool $isAiDebate = false
    ): array {
        $user = User::factory()->create();
        $room = Room::factory()->create([
            'created_by' => $user->id,
            'is_ai_debate' => $isAiDebate,
            'format_type' => 'lincoln_douglas',
        ]);

        $debateData = [
            'room_id' => $room->id,
            $side . '_user_id' => $user->id,
        ];

        if ($isAiDebate) {
            // AIユーザーを作成または取得
            $aiUserId = config('app.ai_user_id', 1);
            $aiUser = User::find($aiUserId) ?? User::factory()->create(['id' => $aiUserId]);
            $otherSide = $side === 'affirmative' ? 'negative' : 'affirmative';
            $debateData[$otherSide . '_user_id'] = $aiUser->id;
        } else {
            $opponent = User::factory()->create();
            $otherSide = $side === 'affirmative' ? 'negative' : 'affirmative';
            $debateData[$otherSide . '_user_id'] = $opponent->id;
        }

        $debate = Debate::factory()->create($debateData);

        // パラメータにdebateが含まれていない場合は追加
        if (!isset($parameters['debate'])) {
            $parameters['debate'] = $debate;
        }

        $livewire = Livewire::actingAs($user)->test($component, $parameters);

        return [
            'livewire' => $livewire,
            'user' => $user,
            'room' => $room,
            'debate' => $debate,
        ];
    }

    /**
     * 複数のユーザーが参加するルームでテスト
     *
     * @param string $component
     * @param array $parameters
     * @param int $userCount
     * @return array ['livewire' => Testable, 'users' => array, 'room' => Room]
     */
    protected function testWithMultiUserRoom(
        string $component,
        array $parameters = [],
        int $userCount = 2
    ): array {
        $users = User::factory()->count($userCount)->create();
        $creator = $users->first();

        $room = Room::factory()->create([
            'created_by' => $creator->id,
            'format_type' => 'lincoln_douglas',
        ]);

        // 全ユーザーをルームに参加させる
        foreach ($users as $index => $user) {
            $room->users()->attach($user->id, [
                'side' => $index % 2 === 0 ? 'affirmative' : 'negative',
            ]);
        }

        // パラメータにroomが含まれていない場合は追加
        if (!isset($parameters['room'])) {
            $parameters['room'] = $room;
        }

        $livewire = Livewire::actingAs($creator)->test($component, $parameters);

        return [
            'livewire' => $livewire,
            'users' => $users,
            'room' => $room,
        ];
    }

    /**
     * イベント発火の検証
     *
     * @param Testable $livewire
     * @param string $eventName
     * @param array|callable|null $eventData
     * @return Testable
     */
    protected function assertEventDispatched(Testable $livewire, string $eventName, $eventData = null): Testable
    {
        if (is_callable($eventData)) {
            return $livewire->assertDispatched($eventName, $eventData);
        } elseif (is_array($eventData)) {
            return $livewire->assertDispatched($eventName, $eventData);
        } else {
            return $livewire->assertDispatched($eventName);
        }
    }

    /**
     * リアルタイムイベント（Echo）のシミュレーション
     *
     * @param Testable $livewire
     * @param string $channel
     * @param string $event
     * @param array $data
     * @return Testable
     */
    protected function simulateRealtimeEvent(
        Testable $livewire,
        string $channel,
        string $event,
        array $data = []
    ): Testable {
        // Livewire 3のEchoイベントを直接メソッド呼び出しでシミュレート

        // コンポーネントのクラス名を取得
        $componentClass = get_class($livewire->instance());

        // イベントに応じて適切なメソッドを呼び出し
        if (($event === 'UserJoinedRoom' || $event === 'UserLeftRoom')) {
            if (strpos($componentClass, 'Participants') !== false) {
                return $livewire->call('updateParticipants');
            } elseif (strpos($componentClass, 'ConnectionStatus') !== false) {
                return $livewire->call('resetState');
            } elseif (strpos($componentClass, 'Status') !== false || strpos($componentClass, 'StartDebateButton') !== false) {
                return $livewire->call('updateStatus', $data);
            }
        }

        // その他のイベントは通常のdispatchを使用
        $echoEventName = "echo-{$channel},{$event}";
        return $livewire->dispatch($echoEventName, $data);
    }

    /**
     * フラッシュメッセージの検証
     *
     * @param Testable $livewire
     * @param string $message
     * @param string $type
     * @return Testable
     */
    protected function assertFlashMessage(
        Testable $livewire,
        string $message,
        string $type = 'info'
    ): Testable {
        return $livewire->assertDispatched('showFlashMessage', $message, $type);
    }

    /**
     * コンポーネントプロパティの一括検証
     *
     * @param Testable $livewire
     * @param array $properties
     * @return Testable
     */
    protected function assertProperties(Testable $livewire, array $properties): Testable
    {
        foreach ($properties as $property => $expectedValue) {
            $livewire->assertSet($property, $expectedValue);
        }

        return $livewire;
    }

    /**
     * バリデーションエラーの一括検証
     *
     * @param Testable $livewire
     * @param array $errors
     * @return Testable
     */
    protected function assertValidationErrors(Testable $livewire, array $errors): Testable
    {
        foreach ($errors as $field => $rules) {
            if (is_array($rules)) {
                $livewire->assertHasErrors([$field => $rules]);
            } else {
                $livewire->assertHasErrors($field);
            }
        }

        return $livewire;
    }

    /**
     * テスト実行後のクリーンアップ
     */
    protected function tearDown(): void
    {
        // Livewire固有のクリーンアップ
        $this->cleanupLivewireTest();

        parent::tearDown();
    }

    /**
     * Livewireテスト用のクリーンアップ
     */
    protected function cleanupLivewireTest(): void
    {
        // セッションクリア
        Session::flush();

        // イベントリスナーのリセット（Fakeの場合は何もしない）
        if (!app('events') instanceof \Illuminate\Support\Testing\Fakes\EventFake) {
            Event::flush();
        }

        // イベントログクリア
        $this->clearEventLogs();
    }
}
