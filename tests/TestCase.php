<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * テスト実行前の共通設定
     */
    protected function setUp(): void
    {
        parent::setUp();

        // テスト用のタイムゾーン設定
        config(['app.timezone' => 'Asia/Tokyo']);

        // テスト用のロケール設定
        app()->setLocale('ja');
    }

    /**
     * テスト実行後のクリーンアップ
     */
    protected function tearDown(): void
    {
        // キャッシュをクリア（モックされていない場合のみ）
        if (app()->bound('cache')) {
            try {
                $cache = cache();
                if (method_exists($cache, 'flush') && !$cache instanceof \Mockery\MockInterface) {
                    $cache->flush();
                }
            } catch (\Exception $e) {
                // モックされたキャッシュの場合は無視
            }
        }

        parent::tearDown();
    }

    /**
     * 認証済みユーザーとしてテストを実行
     */
    protected function actingAsUser($user = null, $guard = null)
    {
        if (!$user) {
            $user = \App\Models\User::factory()->create();
        }

        return $this->actingAs($user, $guard);
    }

    /**
     * 管理者ユーザーとしてテストを実行
     */
    protected function actingAsAdmin($user = null, $guard = null)
    {
        if (!$user) {
            $user = \App\Models\User::factory()->admin()->create();
        }

        return $this->actingAs($user, $guard);
    }

    /**
     * ゲストユーザーとしてテストを実行
     */
    protected function actingAsGuest($user = null, $guard = null)
    {
        if (!$user) {
            $user = \App\Models\User::factory()->guest()->create();
        }

        return $this->actingAs($user, $guard);
    }

    /**
     * データベースの状態をアサート
     */
    protected function assertDatabaseState($expectations)
    {
        foreach ($expectations as $table => $conditions) {
            if (is_array($conditions)) {
                $this->assertDatabaseHas($table, $conditions);
            } else {
                $this->assertDatabaseCount($table, $conditions);
            }
        }
    }

    /**
     * モデルの属性をアサート
     */
    protected function assertModelAttributes($model, array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->assertEquals(
                $value,
                $model->getAttribute($key),
                "Model attribute '{$key}' does not match expected value"
            );
        }
    }

    /**
     * ファクトリーの状態をテスト
     */
    protected function assertFactoryState($factory, $state, $attributes = [])
    {
        $model = $factory->{$state}()->make($attributes);
        $this->assertInstanceOf(get_class($factory->newModel()), $model);
        return $model;
    }
}
