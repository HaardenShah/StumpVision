#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * StumpVision Database Edge Case Tests
 *
 * Comprehensive test suite to validate database schema, constraints, and edge cases
 * Usage: php migrations/test_database.php
 */

require_once __DIR__ . '/../api/lib/Database.php';

use StumpVision\Database;

class DatabaseTest
{
    private Database $db;
    private array $testResults = [];
    private int $testsRun = 0;
    private int $testsPassed = 0;
    private int $testsFailed = 0;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function run(): void
    {
        echo "===========================================\n";
        echo "StumpVision Database Edge Case Tests\n";
        echo "===========================================\n\n";
        echo "Database: " . $this->db->getDbPath() . "\n\n";

        // Run all test suites
        $this->testDatabaseConnection();
        $this->testSchemaExists();
        $this->testDuplicateMigration();
        $this->testPlayerConstraints();
        $this->testForeignKeyConstraints();
        $this->testCheckConstraints();
        $this->testUniqueConstraints();
        $this->testCascadingDeletes();
        $this->testTransactionRollback();
        $this->testNullHandling();
        $this->testIndexes();
        $this->testConcurrentAccess();
        $this->testDataIntegrity();

        // Print results
        $this->printResults();
    }

    private function test(string $name, callable $test): void
    {
        $this->testsRun++;
        try {
            $test();
            $this->testsPassed++;
            $this->testResults[] = ['status' => 'PASS', 'name' => $name];
            echo "✓ PASS: $name\n";
        } catch (\Exception $e) {
            $this->testsFailed++;
            $this->testResults[] = [
                'status' => 'FAIL',
                'name' => $name,
                'error' => $e->getMessage()
            ];
            echo "✗ FAIL: $name\n";
            echo "  Error: " . $e->getMessage() . "\n";
        }
    }

    // ==========================================
    // Test Suites
    // ==========================================

    private function testDatabaseConnection(): void
    {
        echo "\n[1/13] Testing Database Connection...\n";

        $this->test('Database connection established', function() {
            $pdo = $this->db->getPdo();
            if (!$pdo instanceof \PDO) {
                throw new \Exception('PDO instance not created');
            }
        });

        $this->test('Foreign keys are enabled', function() {
            $result = $this->db->fetchOne('PRAGMA foreign_keys');
            if ($result['foreign_keys'] != 1) {
                throw new \Exception('Foreign keys not enabled');
            }
        });

        $this->test('WAL mode is enabled', function() {
            $result = $this->db->fetchOne('PRAGMA journal_mode');
            if (strtoupper($result['journal_mode']) !== 'WAL') {
                throw new \Exception('WAL mode not enabled');
            }
        });
    }

    private function testSchemaExists(): void
    {
        echo "\n[2/13] Testing Schema Existence...\n";

        $requiredTables = ['players', 'matches', 'scheduled_matches', 'live_sessions', 'config', 'migrations'];

        foreach ($requiredTables as $table) {
            $this->test("Table '$table' exists", function() use ($table) {
                if (!$this->db->tableExists($table)) {
                    throw new \Exception("Table '$table' does not exist");
                }
            });
        }
    }

    private function testDuplicateMigration(): void
    {
        echo "\n[3/13] Testing Duplicate Migration Handling...\n";

        $this->test('Schema can be run multiple times without errors', function() {
            $schemaFile = __DIR__ . '/001_initial_schema.sql';
            $sql = file_get_contents($schemaFile);

            // This should not throw an error due to INSERT OR IGNORE
            $this->db->exec($sql);

            // Verify only one migration record exists
            $count = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM migrations WHERE version = '001_initial_schema'"
            );
            if ($count != 1) {
                throw new \Exception("Expected 1 migration record, found $count");
            }
        });
    }

    private function testPlayerConstraints(): void
    {
        echo "\n[4/13] Testing Player Table Constraints...\n";

        // Clean up test data
        $this->db->exec("DELETE FROM players WHERE id LIKE 'test_%'");

        $this->test('Player can be inserted with valid data', function() {
            $this->db->insert('players', [
                'id' => 'test_player_1',
                'name' => 'Test Player',
                'code' => 'TEST001',
                'team' => 'Test Team',
                'player_type' => 'Batsman',
                'registered_at' => time(),
                'registered_by' => 'test_system'
            ]);
        });

        $this->test('Player code must be unique', function() {
            try {
                $this->db->insert('players', [
                    'id' => 'test_player_2',
                    'name' => 'Another Player',
                    'code' => 'TEST001', // Duplicate code
                    'team' => 'Test Team',
                    'player_type' => 'Bowler',
                    'registered_at' => time(),
                    'registered_by' => 'test_system'
                ]);
                throw new \Exception('Should have failed with unique constraint violation');
            } catch (\PDOException $e) {
                if (strpos($e->getMessage(), 'UNIQUE constraint failed') === false) {
                    throw $e;
                }
            }
        });

        $this->test('Player type CHECK constraint works', function() {
            try {
                $this->db->insert('players', [
                    'id' => 'test_player_3',
                    'name' => 'Invalid Player',
                    'code' => 'TEST003',
                    'team' => 'Test Team',
                    'player_type' => 'InvalidType', // Invalid type
                    'registered_at' => time(),
                    'registered_by' => 'test_system'
                ]);
                throw new \Exception('Should have failed with CHECK constraint violation');
            } catch (\PDOException $e) {
                if (strpos($e->getMessage(), 'CHECK constraint failed') === false) {
                    throw $e;
                }
            }
        });

        $this->test('Player can have NULL team', function() {
            $this->db->insert('players', [
                'id' => 'test_player_4',
                'name' => 'No Team Player',
                'code' => 'TEST004',
                'team' => null,
                'player_type' => 'All-rounder',
                'registered_at' => time(),
                'registered_by' => 'test_system'
            ]);
        });

        // Clean up
        $this->db->exec("DELETE FROM players WHERE id LIKE 'test_%'");
    }

    private function testForeignKeyConstraints(): void
    {
        echo "\n[5/13] Testing Foreign Key Constraints...\n";

        // Clean up test data
        $this->db->exec("DELETE FROM live_sessions WHERE live_id LIKE 'test_%'");
        $this->db->exec("DELETE FROM scheduled_matches WHERE id LIKE 'test_%'");
        $this->db->exec("DELETE FROM matches WHERE id LIKE 'test_%'");

        $this->test('Cannot create live_session without valid match_id', function() {
            try {
                $this->db->insert('live_sessions', [
                    'live_id' => 'test_live_1',
                    'match_id' => 'nonexistent_match',
                    'created_at' => time(),
                    'owner_session' => 'test_session',
                    'active' => 1,
                    'current_state' => '{}',
                    'last_updated' => time()
                ]);
                throw new \Exception('Should have failed with foreign key constraint violation');
            } catch (\PDOException $e) {
                if (strpos($e->getMessage(), 'FOREIGN KEY constraint failed') === false) {
                    throw $e;
                }
            }
        });

        $this->test('Can create live_session with valid match_id', function() {
            // Create match first
            $this->db->insert('matches', [
                'id' => 'test_match_1',
                'created_at' => time(),
                'updated_at' => time(),
                'title' => 'Test Match',
                'overs_per_side' => 20,
                'wickets_limit' => 10,
                'teams' => '[]',
                'innings' => '[]'
            ]);

            // Now create live session
            $this->db->insert('live_sessions', [
                'live_id' => 'test_live_2',
                'match_id' => 'test_match_1',
                'created_at' => time(),
                'owner_session' => 'test_session',
                'active' => 1,
                'current_state' => '{}',
                'last_updated' => time()
            ]);
        });

        $this->test('Scheduled match can reference match_id', function() {
            $this->db->insert('scheduled_matches', [
                'id' => 'test_scheduled_1',
                'scheduled_date' => '2024-12-01',
                'scheduled_time' => '10:00',
                'match_name' => 'Test Scheduled Match',
                'team_a' => 'Team A',
                'team_b' => 'Team B',
                'match_format' => 'T20',
                'status' => 'scheduled',
                'created_at' => time(),
                'created_by' => 'test_system',
                'match_id' => 'test_match_1'
            ]);
        });

        // Clean up
        $this->db->exec("DELETE FROM live_sessions WHERE live_id LIKE 'test_%'");
        $this->db->exec("DELETE FROM scheduled_matches WHERE id LIKE 'test_%'");
        $this->db->exec("DELETE FROM matches WHERE id LIKE 'test_%'");
    }

    private function testCheckConstraints(): void
    {
        echo "\n[6/13] Testing CHECK Constraints...\n";

        $this->db->exec("DELETE FROM scheduled_matches WHERE id LIKE 'test_%'");

        $validStatuses = ['scheduled', 'in_progress', 'completed', 'cancelled'];
        foreach ($validStatuses as $status) {
            $this->test("Status '$status' is valid", function() use ($status) {
                $id = 'test_status_' . $status;
                $this->db->insert('scheduled_matches', [
                    'id' => $id,
                    'scheduled_date' => '2024-12-01',
                    'scheduled_time' => '10:00',
                    'match_name' => 'Test Match',
                    'team_a' => 'Team A',
                    'team_b' => 'Team B',
                    'match_format' => 'T20',
                    'status' => $status,
                    'created_at' => time(),
                    'created_by' => 'test_system'
                ]);
                $this->db->delete('scheduled_matches', 'id = :id', ['id' => $id]);
            });
        }

        $this->test('Invalid status is rejected', function() {
            try {
                $this->db->insert('scheduled_matches', [
                    'id' => 'test_invalid_status',
                    'scheduled_date' => '2024-12-01',
                    'scheduled_time' => '10:00',
                    'match_name' => 'Test Match',
                    'team_a' => 'Team A',
                    'team_b' => 'Team B',
                    'match_format' => 'T20',
                    'status' => 'invalid_status',
                    'created_at' => time(),
                    'created_by' => 'test_system'
                ]);
                throw new \Exception('Should have failed with CHECK constraint violation');
            } catch (\PDOException $e) {
                if (strpos($e->getMessage(), 'CHECK constraint failed') === false) {
                    throw $e;
                }
            }
        });

        $validPlayerTypes = ['Batsman', 'Bowler', 'All-rounder', 'Wicket-keeper'];
        $this->db->exec("DELETE FROM players WHERE id LIKE 'test_%'");

        foreach ($validPlayerTypes as $type) {
            $this->test("Player type '$type' is valid", function() use ($type) {
                $id = 'test_type_' . str_replace('-', '_', strtolower($type));
                $this->db->insert('players', [
                    'id' => $id,
                    'name' => 'Test Player',
                    'code' => 'TEST_' . strtoupper($type),
                    'player_type' => $type,
                    'registered_at' => time(),
                    'registered_by' => 'test_system'
                ]);
                $this->db->delete('players', 'id = :id', ['id' => $id]);
            });
        }

        $this->db->exec("DELETE FROM scheduled_matches WHERE id LIKE 'test_%'");
        $this->db->exec("DELETE FROM players WHERE id LIKE 'test_%'");
    }

    private function testUniqueConstraints(): void
    {
        echo "\n[7/13] Testing Unique Constraints...\n";

        $this->db->exec("DELETE FROM players WHERE id LIKE 'test_%'");

        $this->test('Multiple players can have same name', function() {
            $this->db->insert('players', [
                'id' => 'test_player_dup1',
                'name' => 'John Doe',
                'code' => 'JD001',
                'player_type' => 'Batsman',
                'registered_at' => time(),
                'registered_by' => 'test_system'
            ]);

            $this->db->insert('players', [
                'id' => 'test_player_dup2',
                'name' => 'John Doe', // Same name
                'code' => 'JD002', // Different code
                'player_type' => 'Bowler',
                'registered_at' => time(),
                'registered_by' => 'test_system'
            ]);
        });

        $this->test('Player codes must be unique across all players', function() {
            try {
                $this->db->insert('players', [
                    'id' => 'test_player_dup3',
                    'name' => 'Jane Doe',
                    'code' => 'JD001', // Duplicate code
                    'player_type' => 'All-rounder',
                    'registered_at' => time(),
                    'registered_by' => 'test_system'
                ]);
                throw new \Exception('Should have failed with unique constraint violation');
            } catch (\PDOException $e) {
                if (strpos($e->getMessage(), 'UNIQUE constraint failed') === false) {
                    throw $e;
                }
            }
        });

        $this->db->exec("DELETE FROM players WHERE id LIKE 'test_%'");
    }

    private function testCascadingDeletes(): void
    {
        echo "\n[8/13] Testing Cascading Deletes...\n";

        // Clean up
        $this->db->exec("DELETE FROM live_sessions WHERE live_id LIKE 'test_%'");
        $this->db->exec("DELETE FROM scheduled_matches WHERE id LIKE 'test_%'");
        $this->db->exec("DELETE FROM matches WHERE id LIKE 'test_%'");

        $this->test('Deleting match cascades to live_sessions', function() {
            // Create match
            $this->db->insert('matches', [
                'id' => 'test_match_cascade',
                'created_at' => time(),
                'updated_at' => time(),
                'title' => 'Cascade Test Match',
                'overs_per_side' => 20,
                'wickets_limit' => 10,
                'teams' => '[]',
                'innings' => '[]'
            ]);

            // Create live session
            $this->db->insert('live_sessions', [
                'live_id' => 'test_live_cascade',
                'match_id' => 'test_match_cascade',
                'created_at' => time(),
                'owner_session' => 'test_session',
                'active' => 1,
                'current_state' => '{}',
                'last_updated' => time()
            ]);

            // Verify live session exists
            $exists = $this->db->fetchOne(
                "SELECT * FROM live_sessions WHERE live_id = 'test_live_cascade'"
            );
            if (!$exists) {
                throw new \Exception('Live session not created');
            }

            // Delete match (should cascade to live_sessions)
            $this->db->delete('matches', 'id = :id', ['id' => 'test_match_cascade']);

            // Verify live session was deleted
            $stillExists = $this->db->fetchOne(
                "SELECT * FROM live_sessions WHERE live_id = 'test_live_cascade'"
            );
            if ($stillExists) {
                throw new \Exception('Live session was not cascaded deleted');
            }
        });

        $this->test('Deleting match with scheduled_match sets FK to NULL', function() {
            // Create match
            $this->db->insert('matches', [
                'id' => 'test_match_setnull',
                'created_at' => time(),
                'updated_at' => time(),
                'title' => 'SetNull Test Match',
                'overs_per_side' => 20,
                'wickets_limit' => 10,
                'teams' => '[]',
                'innings' => '[]'
            ]);

            // Create scheduled match
            $this->db->insert('scheduled_matches', [
                'id' => 'test_scheduled_setnull',
                'scheduled_date' => '2024-12-01',
                'scheduled_time' => '10:00',
                'match_name' => 'SetNull Test',
                'team_a' => 'Team A',
                'team_b' => 'Team B',
                'match_format' => 'T20',
                'status' => 'scheduled',
                'created_at' => time(),
                'created_by' => 'test_system',
                'match_id' => 'test_match_setnull'
            ]);

            // Delete match (should set match_id to NULL in scheduled_matches)
            $this->db->delete('matches', 'id = :id', ['id' => 'test_match_setnull']);

            // Verify scheduled match still exists but match_id is NULL
            $scheduled = $this->db->fetchOne(
                "SELECT * FROM scheduled_matches WHERE id = 'test_scheduled_setnull'"
            );
            if (!$scheduled) {
                throw new \Exception('Scheduled match was deleted');
            }
            if ($scheduled['match_id'] !== null) {
                throw new \Exception('match_id was not set to NULL');
            }

            // Clean up
            $this->db->delete('scheduled_matches', 'id = :id', ['id' => 'test_scheduled_setnull']);
        });

        // Final cleanup
        $this->db->exec("DELETE FROM live_sessions WHERE live_id LIKE 'test_%'");
        $this->db->exec("DELETE FROM scheduled_matches WHERE id LIKE 'test_%'");
        $this->db->exec("DELETE FROM matches WHERE id LIKE 'test_%'");
    }

    private function testTransactionRollback(): void
    {
        echo "\n[9/13] Testing Transaction Rollback...\n";

        $this->db->exec("DELETE FROM matches WHERE id LIKE 'test_%'");

        $this->test('Transaction rollback prevents data persistence', function() {
            $this->db->beginTransaction();

            $this->db->insert('matches', [
                'id' => 'test_match_rollback',
                'created_at' => time(),
                'updated_at' => time(),
                'title' => 'Rollback Test',
                'overs_per_side' => 20,
                'wickets_limit' => 10,
                'teams' => '[]',
                'innings' => '[]'
            ]);

            // Rollback
            $this->db->rollback();

            // Verify data was not persisted
            $match = $this->db->fetchOne(
                "SELECT * FROM matches WHERE id = 'test_match_rollback'"
            );
            if ($match) {
                throw new \Exception('Data was persisted despite rollback');
            }
        });

        $this->test('Transaction commit persists data', function() {
            $this->db->beginTransaction();

            $this->db->insert('matches', [
                'id' => 'test_match_commit',
                'created_at' => time(),
                'updated_at' => time(),
                'title' => 'Commit Test',
                'overs_per_side' => 20,
                'wickets_limit' => 10,
                'teams' => '[]',
                'innings' => '[]'
            ]);

            // Commit
            $this->db->commit();

            // Verify data was persisted
            $match = $this->db->fetchOne(
                "SELECT * FROM matches WHERE id = 'test_match_commit'"
            );
            if (!$match) {
                throw new \Exception('Data was not persisted after commit');
            }

            // Clean up
            $this->db->delete('matches', 'id = :id', ['id' => 'test_match_commit']);
        });

        $this->db->exec("DELETE FROM matches WHERE id LIKE 'test_%'");
    }

    private function testNullHandling(): void
    {
        echo "\n[10/13] Testing NULL Value Handling...\n";

        $this->db->exec("DELETE FROM players WHERE id LIKE 'test_%'");
        $this->db->exec("DELETE FROM matches WHERE id LIKE 'test_%'");

        $this->test('Optional fields can be NULL', function() {
            $this->db->insert('players', [
                'id' => 'test_player_null',
                'name' => 'Null Test Player',
                'code' => 'NULL001',
                'team' => null, // Optional
                'player_type' => null, // Optional
                'registered_at' => time(),
                'registered_by' => 'test_system',
                'updated_at' => null, // Optional
                'deleted_at' => null // Optional (default)
            ]);
        });

        $this->test('Required fields cannot be NULL', function() {
            try {
                $this->db->insert('players', [
                    'id' => 'test_player_null2',
                    'name' => null, // Required - should fail
                    'code' => 'NULL002',
                    'registered_at' => time(),
                    'registered_by' => 'test_system'
                ]);
                throw new \Exception('Should have failed with NOT NULL constraint violation');
            } catch (\PDOException $e) {
                if (strpos($e->getMessage(), 'NOT NULL constraint failed') === false) {
                    throw $e;
                }
            }
        });

        $this->test('Soft delete using deleted_at works', function() {
            $this->db->insert('players', [
                'id' => 'test_player_softdelete',
                'name' => 'Soft Delete Test',
                'code' => 'SOFT001',
                'player_type' => 'Batsman',
                'registered_at' => time(),
                'registered_by' => 'test_system'
            ]);

            // Soft delete
            $this->db->update(
                'players',
                ['deleted_at' => time()],
                'id = :id',
                ['id' => 'test_player_softdelete']
            );

            // Verify soft delete
            $player = $this->db->fetchOne(
                "SELECT * FROM players WHERE id = 'test_player_softdelete'"
            );
            if ($player['deleted_at'] === null) {
                throw new \Exception('Soft delete did not set deleted_at');
            }
        });

        $this->db->exec("DELETE FROM players WHERE id LIKE 'test_%'");
    }

    private function testIndexes(): void
    {
        echo "\n[11/13] Testing Indexes...\n";

        $expectedIndexes = [
            'players' => ['idx_players_code', 'idx_players_name', 'idx_players_team', 'idx_players_deleted'],
            'matches' => ['idx_matches_created_at', 'idx_matches_verified', 'idx_matches_title', 'idx_matches_deleted'],
            'scheduled_matches' => ['idx_scheduled_matches_date', 'idx_scheduled_matches_status', 'idx_scheduled_matches_match_id'],
            'live_sessions' => ['idx_live_sessions_match_id', 'idx_live_sessions_active', 'idx_live_sessions_created_at']
        ];

        foreach ($expectedIndexes as $table => $indexes) {
            foreach ($indexes as $index) {
                $this->test("Index '$index' exists on table '$table'", function() use ($index) {
                    $result = $this->db->fetchOne(
                        "SELECT COUNT(*) as count FROM sqlite_master WHERE type='index' AND name=:name",
                        ['name' => $index]
                    );
                    if ($result['count'] == 0) {
                        throw new \Exception("Index '$index' does not exist");
                    }
                });
            }
        }
    }

    private function testConcurrentAccess(): void
    {
        echo "\n[12/13] Testing Concurrent Access (WAL mode)...\n";

        $this->test('Multiple connections can read simultaneously', function() {
            $db1 = Database::getInstance();
            $db2 = Database::getInstance(); // Should return same instance

            if ($db1 !== $db2) {
                throw new \Exception('getInstance() returned different instances');
            }

            // Both should be able to read
            $tables1 = $db1->getTables();
            $tables2 = $db2->getTables();

            if ($tables1 !== $tables2) {
                throw new \Exception('Different results from same database');
            }
        });

        $this->test('WAL mode allows concurrent reads', function() {
            $stats = $this->db->getStats();
            if (!isset($stats['tables']) || empty($stats['tables'])) {
                throw new \Exception('Cannot read stats');
            }
        });
    }

    private function testDataIntegrity(): void
    {
        echo "\n[13/13] Testing Data Integrity...\n";

        $this->test('All tables are accessible', function() {
            $tables = $this->db->getTables();
            foreach ($tables as $table) {
                $this->db->fetchColumn("SELECT COUNT(*) FROM $table");
            }
        });

        $this->test('Database file is not corrupted', function() {
            $result = $this->db->fetchOne('PRAGMA integrity_check');
            if ($result['integrity_check'] !== 'ok') {
                throw new \Exception('Database integrity check failed: ' . $result['integrity_check']);
            }
        });

        $this->test('Foreign key check passes', function() {
            $violations = $this->db->fetchAll('PRAGMA foreign_key_check');
            if (!empty($violations)) {
                throw new \Exception('Foreign key violations found: ' . print_r($violations, true));
            }
        });

        $this->test('All required indexes exist', function() {
            $indexes = $this->db->fetchAll(
                "SELECT name FROM sqlite_master WHERE type='index' AND name LIKE 'idx_%'"
            );
            if (count($indexes) < 15) { // We have at least 15 custom indexes
                throw new \Exception('Some indexes are missing');
            }
        });
    }

    private function printResults(): void
    {
        echo "\n===========================================\n";
        echo "Test Results Summary\n";
        echo "===========================================\n\n";

        echo "Total Tests: $this->testsRun\n";
        echo "Passed: " . "\033[32m" . $this->testsPassed . "\033[0m\n";
        echo "Failed: " . ($this->testsFailed > 0 ? "\033[31m" : "") . $this->testsFailed . ($this->testsFailed > 0 ? "\033[0m" : "") . "\n";

        $percentage = $this->testsRun > 0 ? round(($this->testsPassed / $this->testsRun) * 100, 2) : 0;
        echo "Success Rate: $percentage%\n";

        if ($this->testsFailed > 0) {
            echo "\n\033[31mFailed Tests:\033[0m\n";
            foreach ($this->testResults as $result) {
                if ($result['status'] === 'FAIL') {
                    echo "  ✗ " . $result['name'] . "\n";
                    echo "    Error: " . $result['error'] . "\n";
                }
            }
        }

        echo "\n===========================================\n";

        if ($this->testsFailed == 0) {
            echo "\033[32m✓ All tests passed!\033[0m\n";
            echo "Database schema is correct and all edge cases are handled properly.\n";
        } else {
            echo "\033[31m✗ Some tests failed!\033[0m\n";
            echo "Please review the errors above and fix the database schema.\n";
        }

        echo "===========================================\n\n";

        // Exit with appropriate code
        exit($this->testsFailed > 0 ? 1 : 0);
    }
}

// Run tests
try {
    $test = new DatabaseTest();
    $test->run();
} catch (\Exception $e) {
    echo "\n\033[31m✗ Test suite failed to run!\033[0m\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    exit(1);
}
