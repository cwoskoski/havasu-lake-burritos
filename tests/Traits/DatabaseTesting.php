<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;

/**
 * Trait for database-specific testing utilities and assertions.
 * Supports TDD workflow with database state verification.
 */
trait DatabaseTesting
{
    /**
     * Assert that a database table exists and has the expected structure.
     */
    protected function assertTableStructure(string $table, array $expectedColumns): void
    {
        $this->assertTrue(
            Schema::hasTable($table),
            "Table '{$table}' should exist"
        );

        foreach ($expectedColumns as $column => $type) {
            $this->assertTrue(
                Schema::hasColumn($table, $column),
                "Table '{$table}' should have column '{$column}'"
            );

            // For more detailed type checking, you could expand this
            if (is_array($type)) {
                // Handle complex column definitions
                foreach ($type as $constraint) {
                    // Add specific constraint checking here if needed
                }
            }
        }
    }

    /**
     * Assert that specific database constraints exist.
     */
    protected function assertDatabaseConstraints(string $table, array $constraints): void
    {
        foreach ($constraints as $constraint) {
            match ($constraint['type']) {
                'foreign_key' => $this->assertForeignKeyExists(
                    $table,
                    $constraint['column'],
                    $constraint['references']['table'],
                    $constraint['references']['column']
                ),
                'unique' => $this->assertUniqueConstraintExists($table, $constraint['columns']),
                'index' => $this->assertIndexExists($table, $constraint['columns']),
                default => $this->fail("Unknown constraint type: {$constraint['type']}")
            };
        }
    }

    /**
     * Assert that a foreign key constraint exists.
     */
    protected function assertForeignKeyExists(string $table, string $column, string $referencedTable, string $referencedColumn): void
    {
        $foreignKeys = $this->getForeignKeys($table);

        $found = collect($foreignKeys)->contains(function ($fk) use ($column, $referencedTable, $referencedColumn) {
            return $fk['column'] === $column
                && $fk['referenced_table'] === $referencedTable
                && $fk['referenced_column'] === $referencedColumn;
        });

        $this->assertTrue(
            $found,
            "Foreign key constraint from {$table}.{$column} to {$referencedTable}.{$referencedColumn} should exist"
        );
    }

    /**
     * Assert that records were created with proper relationships.
     */
    protected function assertDatabaseRelationship(string $parentTable, string $childTable, array $data): void
    {
        // Verify parent record exists
        $this->assertDatabaseHas($parentTable, $data['parent']);

        // Verify child record exists and is properly linked
        $this->assertDatabaseHas($childTable, $data['child']);

        // Verify the relationship link
        $parentId = DB::table($parentTable)->where($data['parent'])->value('id');
        $this->assertDatabaseHas($childTable, [
            $data['foreign_key'] => $parentId,
            ...$data['child']
        ]);
    }

    /**
     * Assert that database operations are performed within transaction limits.
     */
    protected function assertTransactionPerformance(callable $operation, int $maxQueries = 5): void
    {
        DB::enableQueryLog();
        DB::flushQueryLog();

        $startTime = microtime(true);

        DB::transaction($operation);

        $endTime = microtime(true);
        $queryCount = count(DB::getQueryLog());

        $this->assertLessThanOrEqual(
            $maxQueries,
            $queryCount,
            "Transaction should use no more than {$maxQueries} queries (used {$queryCount})"
        );

        $this->assertLessThan(
            0.1, // 100ms
            $endTime - $startTime,
            'Transaction should complete within 100ms'
        );

        DB::disableQueryLog();
    }

    /**
     * Assert that database seeds create expected data.
     */
    protected function assertSeedData(string $seederClass, array $expectedCounts): void
    {
        // Run the seeder
        $this->artisan('db:seed', ['--class' => $seederClass]);

        // Verify expected record counts
        foreach ($expectedCounts as $table => $count) {
            $actualCount = DB::table($table)->count();
            $this->assertEquals(
                $count,
                $actualCount,
                "Table '{$table}' should have {$count} records after seeding (found {$actualCount})"
            );
        }
    }

    /**
     * Assert that model factories create valid data.
     */
    protected function assertFactoryData(string $modelClass, int $count = 10): void
    {
        $model = new $modelClass;
        $records = $modelClass::factory()->count($count)->make();

        foreach ($records as $record) {
            // Test that the record can be saved (validates relationships, constraints, etc.)
            try {
                $saved = $modelClass::factory()->create($record->toArray());
                $this->assertInstanceOf($modelClass, $saved);
                $this->assertTrue($saved->exists);
            } catch (QueryException $e) {
                $this->fail("Factory for {$modelClass} created invalid data: " . $e->getMessage());
            }
        }
    }

    /**
     * Assert enum values are properly stored and retrieved.
     */
    protected function assertEnumStorage(string $model, string $enumField, array $enumValues): void
    {
        foreach ($enumValues as $enumValue) {
            $record = $model::factory()->create([$enumField => $enumValue]);

            $this->assertEquals(
                $enumValue,
                $record->fresh()->{$enumField},
                "Enum value '{$enumValue}' should be stored and retrieved correctly"
            );
        }
    }

    /**
     * Assert that soft deletes work correctly.
     */
    protected function assertSoftDeletes(string $model): void
    {
        $record = $model::factory()->create();
        $id = $record->id;

        // Delete the record
        $record->delete();

        // Should not be found in normal queries
        $this->assertNull($model::find($id));

        // Should be found in trashed queries
        $this->assertNotNull($model::withTrashed()->find($id));

        // Should be restorable
        $record->restore();
        $this->assertNotNull($model::find($id));
    }

    /**
     * Assert database migration rollback works correctly.
     */
    protected function assertMigrationRollback(string $migration): void
    {
        // This would test that migrations can be rolled back cleanly
        // Implementation depends on specific migration testing strategy
        $this->markTestIncomplete('Migration rollback testing requires specific implementation');
    }

    /**
     * Get foreign key information for a table (MySQL specific).
     */
    private function getForeignKeys(string $table): array
    {
        if (DB::getDriverName() !== 'mysql') {
            return []; // Only implemented for MySQL
        }

        $database = DB::getDatabaseName();

        return DB::select("
            SELECT
                COLUMN_NAME as `column`,
                REFERENCED_TABLE_NAME as referenced_table,
                REFERENCED_COLUMN_NAME as referenced_column
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE
                TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$database, $table]);
    }

    /**
     * Assert that a unique constraint exists.
     */
    private function assertUniqueConstraintExists(string $table, array $columns): void
    {
        // Implementation would check for unique constraints
        $this->markTestIncomplete('Unique constraint checking requires database-specific implementation');
    }

    /**
     * Assert that an index exists.
     */
    private function assertIndexExists(string $table, array $columns): void
    {
        // Implementation would check for indexes
        $this->markTestIncomplete('Index checking requires database-specific implementation');
    }
}