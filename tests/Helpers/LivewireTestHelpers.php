<?php

namespace Tests\Helpers;

use Livewire\Features\SupportTesting\Testable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

/**
 * Livewireテスト用のヘルパートレイト
 *
 * DOM操作、イベントシミュレーション、Alpine.js テスト、
 * 非同期処理テストなどの共通機能を提供
 */
trait LivewireTestHelpers
{
    /**
     * DOM要素の表示確認
     *
     * @param Testable $livewire
     * @param array $elements
     * @return Testable
     */
    protected function assertElementsVisible(Testable $livewire, array $elements): Testable
    {
        foreach ($elements as $element) {
            $livewire->assertSee($element);
        }

        return $livewire;
    }

    /**
     * DOM要素の非表示確認
     *
     * @param Testable $livewire
     * @param array $elements
     * @return Testable
     */
    protected function assertElementsHidden(Testable $livewire, array $elements): Testable
    {
        foreach ($elements as $element) {
            $livewire->assertDontSee($element);
        }

        return $livewire;
    }

    /**
     * HTMLタグ内容の確認
     *
     * @param Testable $livewire
     * @param array $htmlElements
     * @return Testable
     */
    protected function assertHtmlElements(Testable $livewire, array $htmlElements): Testable
    {
        foreach ($htmlElements as $html) {
            $livewire->assertSeeHtml($html);
        }

        return $livewire;
    }

    /**
     * イベントシーケンスのテスト
     *
     * @param Testable $livewire
     * @param array $eventSequence
     * @return Testable
     */
    protected function dispatchEventSequence(Testable $livewire, array $eventSequence): Testable
    {
        foreach ($eventSequence as $event) {
            if (is_array($event)) {
                $eventName = $event['name'];
                $eventData = $event['data'] ?? [];
                $livewire->dispatch($eventName, $eventData);
            } else {
                $livewire->dispatch($event);
            }
        }

        return $livewire;
    }

    /**
     * フォームフィールドの一括設定
     *
     * @param Testable $livewire
     * @param array $fields
     * @return Testable
     */
    protected function fillForm(Testable $livewire, array $fields): Testable
    {
        foreach ($fields as $field => $value) {
            $livewire->set($field, $value);
        }

        return $livewire;
    }

    /**
     * 複数のアクションを順次実行
     *
     * @param Testable $livewire
     * @param array $actions
     * @return Testable
     */
    protected function performActions(Testable $livewire, array $actions): Testable
    {
        foreach ($actions as $action) {
            if (is_array($action)) {
                $method = $action['method'];
                $parameters = $action['parameters'] ?? [];
                $livewire->call($method, ...$parameters);
            } else {
                $livewire->call($action);
            }
        }

        return $livewire;
    }

    /**
     * タイムスタンプベースのプロパティ検証
     *
     * @param Testable $livewire
     * @param string $property
     * @param Carbon|string|int $expectedTime
     * @param int $toleranceSeconds
     * @return bool
     */
    protected function assertTimestampProperty(
        Testable $livewire,
        string $property,
        $expectedTime,
        int $toleranceSeconds = 5
    ): bool {
        $component = $livewire->instance();
        $actualValue = $component->{$property};

        if ($expectedTime instanceof Carbon) {
            $expectedTimestamp = $expectedTime->timestamp;
        } elseif (is_string($expectedTime)) {
            $expectedTimestamp = Carbon::parse($expectedTime)->timestamp;
        } else {
            $expectedTimestamp = $expectedTime;
        }

        if (is_null($actualValue)) {
            return is_null($expectedTime);
        }

        $actualTimestamp = is_numeric($actualValue) ? $actualValue : Carbon::parse($actualValue)->timestamp;

        return abs($actualTimestamp - $expectedTimestamp) <= $toleranceSeconds;
    }

    /**
     * コンポーネントの状態をスナップショット
     *
     * @param Testable $livewire
     * @param array $properties
     * @return array
     */
    protected function snapshotComponentState(Testable $livewire, array $properties = []): array
    {
        $component = $livewire->instance();
        $snapshot = [];

        if (empty($properties)) {
            // 全プロパティを取得
            $reflection = new \ReflectionClass($component);
            $properties = array_map(
                fn($prop) => $prop->getName(),
                $reflection->getProperties(\ReflectionProperty::IS_PUBLIC)
            );
        }

        foreach ($properties as $property) {
            if (property_exists($component, $property)) {
                $snapshot[$property] = $component->{$property};
            }
        }

        return $snapshot;
    }

    /**
     * スナップショットとの差分確認
     *
     * @param Testable $livewire
     * @param array $snapshot
     * @param array $expectedChanges
     * @return Testable
     */
    protected function assertStateChanges(
        Testable $livewire,
        array $snapshot,
        array $expectedChanges
    ): Testable {
        $component = $livewire->instance();

        foreach ($expectedChanges as $property => $expectedValue) {
            $oldValue = $snapshot[$property] ?? null;
            $currentValue = $component->{$property} ?? null;

            if ($expectedValue !== $currentValue) {
                throw new \PHPUnit\Framework\AssertionFailedError(
                    "Property '{$property}' expected to be '{$expectedValue}', got '{$currentValue}'"
                );
            }
        }

        return $livewire;
    }

    /**
     * セッションデータの設定
     *
     * @param array $sessionData
     * @return void
     */
    protected function setSessionData(array $sessionData): void
    {
        foreach ($sessionData as $key => $value) {
            Session::put($key, $value);
        }
    }

    /**
     * セッションフラッシュデータの設定
     *
     * @param array $flashData
     * @return void
     */
    protected function setFlashData(array $flashData): void
    {
        foreach ($flashData as $key => $value) {
            Session::flash($key, $value);
        }
    }

    /**
     * Alpine.js のプロパティシミュレーション
     *
     * @param Testable $livewire
     * @param string $property
     * @param mixed $value
     * @return Testable
     */
    protected function setAlpineProperty(Testable $livewire, string $property, $value): Testable
    {
        // Alpine.jsの動作をシミュレーション（JavaScriptイベントとして）
        return $livewire->dispatch('alpine-property-changed', [
            'property' => $property,
            'value' => $value
        ]);
    }

    /**
     * 非同期処理の完了を待機
     *
     * @param Testable $livewire
     * @param int $maxWaitSeconds
     * @return Testable
     */
    protected function waitForAsyncCompletion(Testable $livewire, int $maxWaitSeconds = 5): Testable
    {
        // Livewireでは実際の非同期処理は待機できないため、
        // refreshを呼んでコンポーネントの状態を更新
        $livewire->refresh();

        return $livewire;
    }

    /**
     * 条件分岐テストヘルパー
     *
     * @param Testable $livewire
     * @param callable $condition
     * @param callable $trueCase
     * @param callable|null $falseCase
     * @return Testable
     */
    protected function conditionalTest(
        Testable $livewire,
        callable $condition,
        callable $trueCase,
        callable $falseCase = null
    ): Testable {
        if ($condition($livewire)) {
            $trueCase($livewire);
        } elseif ($falseCase) {
            $falseCase($livewire);
        }

        return $livewire;
    }

    /**
     * エラー状態のシミュレーション
     *
     * @param Testable $livewire
     * @param string $errorType
     * @param array $errorData
     * @return Testable
     */
    protected function simulateError(Testable $livewire, string $errorType, array $errorData = []): Testable
    {
        switch ($errorType) {
            case 'network':
                // ネットワークエラーシミュレーション
                return $livewire->dispatch('network-error', $errorData);

            case 'timeout':
                // タイムアウトエラーシミュレーション
                return $livewire->dispatch('timeout-error', $errorData);

            case 'validation':
                // バリデーションエラーシミュレーション
                return $livewire->dispatch('validation-error', $errorData);

            default:
                // 一般的なエラーシミュレーション
                return $livewire->dispatch('error', array_merge(['type' => $errorType], $errorData));
        }
    }

    /**
     * リアルタイムイベントのバッチ処理
     *
     * @param Testable $livewire
     * @param array $events
     * @return Testable
     */
    protected function dispatchRealtimeEvents(Testable $livewire, array $events): Testable
    {
        foreach ($events as $event) {
            $channel = $event['channel'];
            $eventName = $event['event'];
            $data = $event['data'] ?? [];

            $echoEventName = "echo-{$channel},{$eventName}";
            $livewire->dispatch($echoEventName, $data);
        }

        return $livewire;
    }

    /**
     * デバッグ用：コンポーネント状態の出力
     *
     * @param Testable $livewire
     * @param array $properties
     * @return Testable
     */
    protected function dumpComponentState(Testable $livewire, array $properties = []): Testable
    {
        $component = $livewire->instance();

        if (empty($properties)) {
            $reflection = new \ReflectionClass($component);
            $properties = array_map(
                fn($prop) => $prop->getName(),
                $reflection->getProperties(\ReflectionProperty::IS_PUBLIC)
            );
        }

        $state = [];
        foreach ($properties as $property) {
            if (property_exists($component, $property)) {
                $state[$property] = $component->{$property};
            }
        }

        dump("Component State:", $state);

        return $livewire;
    }
}
