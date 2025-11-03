<?php
declare(strict_types=1);

/**
 * StumpVision Web-based Database Schema Migration
 *
 * This script can be run from a web browser to initialize the database schema.
 * It's designed for situations where command-line access is not available.
 *
 * Usage:
 *   Navigate to: https://cricket.haardenshah.com/migrations/web_migrate.php
 *
 * Security: Remove this file after migration is complete!
 */

// Set display errors for debugging
ini_set('display_errors', '1');
error_reporting(E_ALL);

// Set content type
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StumpVision Database Migration</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-top: 0;
        }
        .status {
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
            font-family: monospace;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .log {
            background: #f8f9fa;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
            border: 1px solid #dee2e6;
            max-height: 400px;
            overflow-y: auto;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .button:hover {
            background: #0056b3;
        }
        .button.danger {
            background: #dc3545;
        }
        .button.danger:hover {
            background: #c82333;
        }
        ul {
            line-height: 1.8;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏏 StumpVision Database Migration</h1>

<?php

require_once __DIR__ . '/../api/lib/Database.php';

use StumpVision\Database;

// Check if migration should run
$runMigration = isset($_GET['run']) && $_GET['run'] === '1';
$force = isset($_GET['force']) && $_GET['force'] === '1';

if (!$runMigration) {
    // Show pre-migration information
    echo '<div class="info">';
    echo '<strong>ℹ️ Database Migration Required</strong><br><br>';
    echo 'This script will initialize the StumpVision database schema.<br>';
    echo 'It will create the following tables:';
    echo '</div>';

    echo '<ul>';
    echo '<li><code>players</code> - Player registration data</li>';
    echo '<li><code>matches</code> - Match records</li>';
    echo '<li><code>scheduled_matches</code> - Scheduled matches</li>';
    echo '<li><code>live_sessions</code> - Live match sessions</li>';
    echo '<li><code>config</code> - Configuration settings</li>';
    echo '<li><code>migrations</code> - Migration tracking</li>';
    echo '</ul>';

    try {
        $db = Database::getInstance();
        $dbPath = $db->getDbPath();

        echo '<div class="status success">';
        echo '✓ Database file found<br>';
        echo 'Path: <code>' . htmlspecialchars($dbPath) . '</code>';
        echo '</div>';

        // Check if already migrated
        if ($db->tableExists('migrations')) {
            echo '<div class="warning">';
            echo '<strong>⚠️ Warning:</strong> Database appears to already have been migrated.<br><br>';
            echo 'The <code>migrations</code> table already exists. Running this migration again may cause errors.<br><br>';

            // Show existing migrations
            try {
                $existingMigrations = $db->fetchAll("SELECT * FROM migrations ORDER BY applied_at");
                if (!empty($existingMigrations)) {
                    echo '<strong>Previous migrations:</strong><br>';
                    foreach ($existingMigrations as $migration) {
                        $date = date('Y-m-d H:i:s', $migration['applied_at']);
                        echo '• ' . htmlspecialchars($migration['version']) . ' (' . htmlspecialchars($date) . ')<br>';
                    }
                }
            } catch (Exception $e) {
                // Ignore errors reading migrations
            }

            echo '</div>';

            echo '<p><strong>Options:</strong></p>';
            echo '<p>';
            echo '<a href="?run=1&force=1" class="button danger" onclick="return confirm(\'Are you sure? This may cause errors if tables already exist.\')">Force Run Migration Anyway</a> ';
            echo '<a href="../admin/" class="button">Go to Admin Panel</a>';
            echo '</p>';
        } else {
            echo '<div class="info">';
            echo '✓ Database is ready for migration';
            echo '</div>';

            echo '<p><a href="?run=1" class="button">Run Migration Now</a></p>';
        }

    } catch (Exception $e) {
        echo '<div class="error">';
        echo '<strong>✗ Error:</strong><br>';
        echo htmlspecialchars($e->getMessage());
        echo '</div>';
    }

    echo '<hr>';
    echo '<p style="color: #666; font-size: 12px;">';
    echo '<strong>⚠️ Security Notice:</strong> Please delete this file after migration is complete!<br>';
    echo 'Command: <code>rm migrations/web_migrate.php</code>';
    echo '</p>';

} else {
    // Run the migration
    echo '<h2>Migration Progress</h2>';
    echo '<div class="log">';

    $log = [];
    $success = false;

    try {
        $log[] = "===========================================";
        $log[] = "StumpVision Database Schema Migration";
        $log[] = "===========================================";
        $log[] = "";

        // Get database instance
        $db = Database::getInstance();
        $log[] = "✓ Database connection established";
        $log[] = "  Database path: " . $db->getDbPath();
        $log[] = "";

        // Check if migrations table exists
        $previouslyMigrated = $db->tableExists('migrations');

        if ($previouslyMigrated && !$force) {
            throw new Exception("Database already migrated. Use force=1 parameter to override.");
        }

        if ($previouslyMigrated) {
            $log[] = "⚠ WARNING: Database already has migrations table.";
            $log[] = "  Forcing migration anyway...";
            $log[] = "";
        }

        // Read the schema SQL file
        $schemaFile = __DIR__ . '/001_initial_schema.sql';
        if (!file_exists($schemaFile)) {
            throw new Exception("Schema file not found: $schemaFile");
        }

        $log[] = "Reading schema file...";
        $sql = file_get_contents($schemaFile);
        if ($sql === false) {
            throw new Exception("Failed to read schema file");
        }

        // Execute the schema
        $log[] = "Applying database schema...";
        $log[] = "";

        $db->beginTransaction();

        try {
            $db->exec($sql);
            $db->commit();

            $log[] = "✓ Schema applied successfully!";
            $log[] = "";

            // Show created tables
            $tables = $db->getTables();
            $log[] = "Created tables:";
            foreach ($tables as $table) {
                $log[] = "  - $table";
            }

            $log[] = "";

            // Show database info
            $stats = $db->getStats();
            $log[] = "Database statistics:";
            $log[] = "  - File size: {$stats['file_size_mb']} MB";
            $log[] = "  - Table count: {$stats['table_count']}";

            $log[] = "";
            $log[] = "===========================================";
            $log[] = "✓ Migration completed successfully!";
            $log[] = "===========================================";

            $success = true;

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        $log[] = "";
        $log[] = "✗ Migration FAILED!";
        $log[] = "  Error: " . $e->getMessage();
        $log[] = "  File: " . $e->getFile() . ":" . $e->getLine();
        $log[] = "";
    }

    // Output log
    echo htmlspecialchars(implode("\n", $log));
    echo '</div>';

    if ($success) {
        echo '<div class="status success">';
        echo '<strong>✓ Migration completed successfully!</strong><br><br>';
        echo 'Your database is now ready to use.';
        echo '</div>';

        echo '<h3>Next Steps:</h3>';
        echo '<ol>';
        echo '<li>Test the admin panel: <a href="../admin/">Go to Admin Panel</a></li>';
        echo '<li>Import data from JSON files (if needed): Run <code>php migrations/import_from_files.php</code></li>';
        echo '<li><strong>Delete this migration script for security</strong></li>';
        echo '</ol>';

        echo '<p><a href="../admin/" class="button">Go to Admin Panel</a></p>';

    } else {
        echo '<div class="status error">';
        echo '<strong>✗ Migration failed!</strong><br><br>';
        echo 'Please check the error log above for details.';
        echo '</div>';

        echo '<p><a href="?" class="button">Try Again</a></p>';
    }

    echo '<hr>';
    echo '<p style="color: #666; font-size: 12px;">';
    echo '<strong>⚠️ Security Notice:</strong> Please delete this file after migration is complete!<br>';
    echo 'Command: <code>rm migrations/web_migrate.php</code>';
    echo '</p>';
}

?>

    </div>
</body>
</html>
