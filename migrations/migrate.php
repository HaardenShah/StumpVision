#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * StumpVision Database Schema Migration Runner
 *
 * Usage:
 *   php migrations/migrate.php
 *
 * This script applies the database schema to create all tables and indexes.
 */

require_once __DIR__ . '/../api/lib/Database.php';

use StumpVision\Database;

echo "===========================================\n";
echo "StumpVision Database Schema Migration\n";
echo "===========================================\n\n";

try {
    // Get database instance
    $db = Database::getInstance();
    echo "✓ Database connection established\n";
    echo "  Database path: " . $db->getDbPath() . "\n\n";

    // Check if migrations table exists (indicates previous migration)
    $previouslyMigrated = $db->tableExists('migrations');

    if ($previouslyMigrated) {
        echo "⚠ WARNING: Database already has migrations table.\n";
        echo "  This may indicate the schema was previously applied.\n";

        // Show existing migrations
        $existingMigrations = $db->fetchAll("SELECT * FROM migrations ORDER BY applied_at");
        if (!empty($existingMigrations)) {
            echo "\n  Previous migrations:\n";
            foreach ($existingMigrations as $migration) {
                $date = date('Y-m-d H:i:s', $migration['applied_at']);
                echo "    - {$migration['version']} ({$date})\n";
            }
        }

        echo "\n  Do you want to continue anyway? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);

        if (strtolower($line) !== 'yes') {
            echo "\n✗ Migration cancelled by user.\n";
            exit(0);
        }
        echo "\n";
    }

    // Read the schema SQL file
    $schemaFile = __DIR__ . '/001_initial_schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }

    echo "Reading schema file...\n";
    $sql = file_get_contents($schemaFile);
    if ($sql === false) {
        throw new Exception("Failed to read schema file");
    }

    // Execute the schema
    echo "Applying database schema...\n\n";

    $db->beginTransaction();

    try {
        $db->exec($sql);
        $db->commit();

        echo "✓ Schema applied successfully!\n\n";

        // Show created tables
        $tables = $db->getTables();
        echo "Created tables:\n";
        foreach ($tables as $table) {
            echo "  - $table\n";
        }

        echo "\n";

        // Show database info
        $stats = $db->getStats();
        echo "Database statistics:\n";
        echo "  - File size: {$stats['file_size_mb']} MB\n";
        echo "  - Table count: {$stats['table_count']}\n";

        echo "\n===========================================\n";
        echo "✓ Migration completed successfully!\n";
        echo "===========================================\n\n";

        echo "Next steps:\n";
        echo "  1. Run data import: php migrations/import_from_files.php\n";
        echo "  2. Verify data integrity\n";
        echo "  3. Test API endpoints\n\n";

    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }

} catch (Exception $e) {
    echo "\n✗ Migration FAILED!\n";
    echo "  Error: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    exit(1);
}
