<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\DB;

trait AssertsDatabaseState
{
    /**
     * Assert that a record exists in the database with specific attributes
     */
    protected function assertDatabaseHasRecord(string $table, array $attributes): void
    {
        $this->assertDatabaseHas($table, $attributes);
    }

    /**
     * Assert that a record does not exist in the database
     */
    protected function assertDatabaseMissingRecord(string $table, array $attributes): void
    {
        $this->assertDatabaseMissing($table, $attributes);
    }

    /**
     * Assert database record count
     */
    protected function assertDatabaseRecordCount(string $table, int $expectedCount, array $where = []): void
    {
        if (empty($where)) {
            $this->assertDatabaseCount($table, $expectedCount);
        } else {
            $actualCount = DB::table($table)->where($where)->count();
            $this->assertEquals(
                $expectedCount,
                $actualCount,
                "Expected {$expectedCount} records in {$table} but found {$actualCount}"
            );
        }
    }

    /**
     * Assert model was created in database
     */
    protected function assertModelCreated($model): void
    {
        $this->assertTrue($model->exists, 'Model should exist in database');
        $this->assertNotNull($model->id, 'Model should have an ID');
        $this->assertDatabaseHas($model->getTable(), [$model->getKeyName() => $model->getKey()]);
    }

    /**
     * Assert model was updated in database
     */
    protected function assertModelUpdated($model, array $expectedChanges): void
    {
        $this->assertTrue($model->exists, 'Model should exist in database');

        foreach ($expectedChanges as $attribute => $expectedValue) {
            $this->assertEquals(
                $expectedValue,
                $model->getAttribute($attribute),
                "Model attribute '{$attribute}' was not updated correctly"
            );
        }

        $this->assertDatabaseHas($model->getTable(), array_merge(
            [$model->getKeyName() => $model->getKey()],
            $expectedChanges
        ));
    }

    /**
     * Assert model was soft deleted
     */
    protected function assertModelSoftDeleted($model): void
    {
        $this->assertTrue($model->trashed(), 'Model should be soft deleted');
        $this->assertNotNull($model->deleted_at, 'Model should have deleted_at timestamp');
        $this->assertDatabaseHas($model->getTable(), [
            $model->getKeyName() => $model->getKey(),
            'deleted_at' => $model->deleted_at,
        ]);
    }

    /**
     * Assert model was permanently deleted
     */
    protected function assertModelDeleted($model): void
    {
        $this->assertDatabaseMissing($model->getTable(), [
            $model->getKeyName() => $model->getKey()
        ]);
    }

    /**
     * Assert relationship exists in database
     */
    protected function assertRelationshipExists(string $table, array $relationshipData): void
    {
        $this->assertDatabaseHas($table, $relationshipData);
    }

    /**
     * Assert relationship does not exist in database
     */
    protected function assertRelationshipMissing(string $table, array $relationshipData): void
    {
        $this->assertDatabaseMissing($table, $relationshipData);
    }

    /**
     * Assert pivot table relationship
     */
    protected function assertPivotRelationship($parentModel, $relatedModel, string $relationName, array $pivotData = []): void
    {
        $relation = $parentModel->{$relationName}();
        $pivotTable = $relation->getTable();

        $expectedData = [
            $relation->getForeignPivotKeyName() => $parentModel->getKey(),
            $relation->getRelatedPivotKeyName() => $relatedModel->getKey(),
        ];

        $expectedData = array_merge($expectedData, $pivotData);

        $this->assertDatabaseHas($pivotTable, $expectedData);
    }

    /**
     * Assert multiple database states
     */
    protected function assertMultipleDatabaseStates(array $expectations): void
    {
        foreach ($expectations as $table => $conditions) {
            if (is_int($conditions)) {
                $this->assertDatabaseCount($table, $conditions);
            } elseif (is_array($conditions)) {
                $this->assertDatabaseHas($table, $conditions);
            }
        }
    }

    /**
     * Assert timestamp columns are set correctly
     */
    protected function assertTimestampsSet($model): void
    {
        $this->assertNotNull($model->created_at, 'created_at should be set');
        $this->assertNotNull($model->updated_at, 'updated_at should be set');
        $this->assertTrue(
            $model->created_at->lessThanOrEqualTo($model->updated_at),
            'created_at should be less than or equal to updated_at'
        );
    }
}
