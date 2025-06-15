<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;

abstract class BaseModelTest extends TestCase
{
    /**
     * テスト対象のモデルクラス名
     */
    protected string $modelClass;

    /**
     * テスト対象のファクトリー
     */
    protected $factory;

    /**
     * テスト用のモデルインスタンス
     */
    protected Model $model;

    /**
     * テスト実行前の設定
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (isset($this->modelClass)) {
            $this->factory = $this->modelClass::factory();
            $this->model = $this->factory->make();
        }
    }

    /**
     * モデルの基本属性をテスト
     */
    protected function assertModelBasics(array $fillable = null, array $hidden = null, array $casts = null)
    {
        if ($fillable !== null) {
            $this->assertEquals($fillable, $this->model->getFillable());
        }

        if ($hidden !== null) {
            $this->assertEquals($hidden, $this->model->getHidden());
        }

        if ($casts !== null) {
            $this->assertEquals($casts, $this->model->getCasts());
        }
    }

    /**
     * ファクトリーでモデルを作成できることをテスト
     */
    protected function assertFactoryCreation()
    {
        $model = $this->factory->create();
        $this->assertInstanceOf($this->modelClass, $model);
        $this->assertTrue($model->exists);
        $this->assertNotNull($model->id);
    }

    /**
     * リレーションシップをテスト
     */
    protected function assertRelationship(string $relationName, string $expectedType, string $relatedModel = null)
    {
        $model = $this->factory->create();
        $relation = $model->{$relationName}();

        $this->assertInstanceOf($expectedType, $relation);

        if ($relatedModel) {
            $this->assertEquals($relatedModel, $relation->getRelated()::class);
        }
    }

    /**
     * belongsTo リレーションシップをテスト
     */
    protected function assertBelongsTo(string $relationName, string $relatedModel)
    {
        $this->assertRelationship(
            $relationName,
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $relatedModel
        );
    }

    /**
     * hasMany リレーションシップをテスト
     */
    protected function assertHasMany(string $relationName, string $relatedModel)
    {
        $this->assertRelationship(
            $relationName,
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $relatedModel
        );
    }

    /**
     * hasOne リレーションシップをテスト
     */
    protected function assertHasOne(string $relationName, string $relatedModel)
    {
        $this->assertRelationship(
            $relationName,
            \Illuminate\Database\Eloquent\Relations\HasOne::class,
            $relatedModel
        );
    }

    /**
     * belongsToMany リレーションシップをテスト
     */
    protected function assertBelongsToMany(string $relationName, string $relatedModel)
    {
        $this->assertRelationship(
            $relationName,
            \Illuminate\Database\Eloquent\Relations\BelongsToMany::class,
            $relatedModel
        );
    }

    /**
     * モデルの属性の型キャストをテスト
     */
    protected function assertAttributeCast(string $attribute, $value, string $expectedType)
    {
        $model = $this->factory->create([$attribute => $value]);
        $castValue = $model->getAttribute($attribute);

        $this->assertInstanceOf($expectedType, $castValue);
    }

    /**
     * モデルのバリデーションルールをテスト
     */
    protected function assertValidationRules(array $data, array $expectedErrors = [])
    {
        $model = $this->factory->make($data);

        if (method_exists($model, 'rules')) {
            $validator = Validator::make($data, $model->rules());

            if (empty($expectedErrors)) {
                $this->assertTrue($validator->passes());
            } else {
                $this->assertTrue($validator->fails());
                foreach ($expectedErrors as $field) {
                    $this->assertTrue($validator->errors()->has($field));
                }
            }
        }
    }

    /**
     * ソフトデリート機能をテスト
     */
    protected function assertSoftDeletes()
    {
        $model = $this->factory->create();
        $id = $model->id;

        // ソフトデリートを実行
        $model->delete();

        // データベースから削除されていないことを確認
        $this->assertDatabaseHas($model->getTable(), ['id' => $id]);

        // deleted_at が設定されていることを確認
        $this->assertNotNull($model->fresh()->deleted_at);

        // withTrashed で取得できることを確認
        $trashedModel = $this->modelClass::withTrashed()->find($id);
        $this->assertNotNull($trashedModel);
    }

    /**
     * モデルのスコープをテスト
     */
    protected function assertScope(string $scopeName, array $parameters = [], int $expectedCount = null)
    {
        $query = $this->modelClass::query()->{$scopeName}(...$parameters);

        if ($expectedCount !== null) {
            $this->assertEquals($expectedCount, $query->count());
        }

        return $query;
    }

    /**
     * イベントの発火をテスト
     */
    protected function assertEventFired(string $eventClass, callable $action)
    {
        Event::fake();

        $action();

        Event::assertDispatched($eventClass);
    }

    /**
     * ファクトリー状態のテスト
     */
    protected function assertFactoryStates(array $states)
    {
        foreach ($states as $state) {
            $model = $this->factory->{$state}()->create();
            $this->assertInstanceOf($this->modelClass, $model);
            $this->assertTrue($model->exists);
        }
    }
}
