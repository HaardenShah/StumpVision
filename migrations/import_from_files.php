#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * StumpVision Data Import from JSON Files
 *
 * Usage:
 *   php migrations/import_from_files.php
 *
 * This script imports all existing data from JSON files into the database.
 * Run this AFTER running migrate.php to create the schema.
 */

require_once __DIR__ . '/../api/lib/Common.php';
require_once __DIR__ . '/../api/lib/Database.php';

use StumpVision\Common;
use StumpVision\Database;

echo "===========================================\n";
echo "StumpVision Data Import from Files\n";
echo "===========================================\n\n";

$dataDir = __DIR__ . '/../data';
$report = [
    'players' => ['success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []],
    'matches' => ['success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []],
    'scheduled_matches' => ['success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []],
    'live_sessions' => ['success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []]
];

try {
    // Get database instance
    $db = Database::getInstance();
    echo "✓ Database connection established\n";
    echo "  Database path: " . $db->getDbPath() . "\n\n";

    // Verify schema exists
    if (!$db->tableExists('players') || !$db->tableExists('matches')) {
        throw new Exception("Database schema not found. Please run migrate.php first.");
    }

    echo "✓ Database schema verified\n\n";

    // Start transaction
    echo "Starting data import...\n";
    echo "This may take a while depending on the amount of data.\n\n";

    $db->beginTransaction();

    // ==========================================
    // 1. Import Players
    // ==========================================
    echo "[1/4] Importing players...\n";

    $playersFile = $dataDir . '/players.json';
    if (file_exists($playersFile)) {
        $result = Common::safeJsonRead($playersFile);

        if ($result['ok']) {
            $players = $result['data'];

            if (is_array($players) && !empty($players)) {
                foreach ($players as $playerId => $player) {
                    try {
                        // Skip if player ID doesn't match (sanity check)
                        if (isset($player['id']) && $player['id'] !== $playerId) {
                            $report['players']['skipped']++;
                            $report['players']['errors'][] = "Player ID mismatch: {$playerId} != {$player['id']}";
                            continue;
                        }

                        $db->insert('players', [
                            'id' => $player['id'] ?? $playerId,
                            'name' => $player['name'] ?? 'Unknown',
                            'code' => $player['code'] ?? 'UNKN-0000',
                            'team' => $player['team'] ?? '',
                            'player_type' => $player['player_type'] ?? 'Batsman',
                            'registered_at' => $player['registered_at'] ?? time(),
                            'registered_by' => $player['registered_by'] ?? 'admin',
                            'updated_at' => $player['updated_at'] ?? null
                        ]);

                        $report['players']['success']++;

                    } catch (Exception $e) {
                        $report['players']['failed']++;
                        $report['players']['errors'][] = "Player {$playerId}: " . $e->getMessage();
                    }
                }

                echo "  ✓ Imported {$report['players']['success']} players\n";
                if ($report['players']['failed'] > 0) {
                    echo "  ⚠ Failed: {$report['players']['failed']}\n";
                }
                if ($report['players']['skipped'] > 0) {
                    echo "  ⚠ Skipped: {$report['players']['skipped']}\n";
                }
            } else {
                echo "  - No players found in file\n";
            }
        } else {
            echo "  ⚠ Failed to read players.json: {$result['error']}\n";
        }
    } else {
        echo "  - players.json not found (skipping)\n";
    }

    echo "\n";

    // ==========================================
    // 2. Import Matches
    // ==========================================
    echo "[2/4] Importing matches...\n";

    $matchFiles = glob($dataDir . '/*.json');

    if ($matchFiles !== false && !empty($matchFiles)) {
        foreach ($matchFiles as $file) {
            $filename = basename($file);

            // Skip non-match files
            if (in_array($filename, ['players.json', 'scheduled-matches.json'])) {
                continue;
            }

            $matchId = basename($file, '.json');
            $result = Common::safeJsonRead($file);

            if ($result['ok']) {
                $match = $result['data'];

                try {
                    // Extract metadata
                    $meta = $match['meta'] ?? [];
                    $title = $meta['title'] ?? 'Untitled Match';
                    $oversPerSide = $meta['oversPerSide'] ?? 20;
                    $wicketsLimit = $meta['wicketsLimit'] ?? 10;

                    // Prepare teams and innings as JSON
                    $teams = $match['teams'] ?? [];
                    $innings = $match['innings'] ?? [];

                    // Extract verification info
                    $verified = isset($match['__verified']) && $match['__verified'] ? 1 : 0;
                    $verifiedAt = $match['__verified_at'] ?? null;
                    $verifiedBy = $match['__verified_by'] ?? null;

                    // Extract timestamps
                    $createdAt = $match['__saved_at'] ?? filemtime($file);
                    $updatedAt = $match['__saved_at'] ?? filemtime($file);

                    // Extract version
                    $version = $match['__version'] ?? '2.3';

                    $db->insert('matches', [
                        'id' => $matchId,
                        'created_at' => $createdAt,
                        'updated_at' => $updatedAt,
                        'title' => $title,
                        'overs_per_side' => $oversPerSide,
                        'wickets_limit' => $wicketsLimit,
                        'teams' => json_encode($teams),
                        'innings' => json_encode($innings),
                        'verified' => $verified,
                        'verified_at' => $verifiedAt,
                        'verified_by' => $verifiedBy,
                        'version' => $version
                    ]);

                    $report['matches']['success']++;

                } catch (Exception $e) {
                    $report['matches']['failed']++;
                    $report['matches']['errors'][] = "Match {$matchId}: " . $e->getMessage();
                }
            } else {
                $report['matches']['failed']++;
                $report['matches']['errors'][] = "Match {$matchId}: Failed to read file - {$result['error']}";
            }
        }

        echo "  ✓ Imported {$report['matches']['success']} matches\n";
        if ($report['matches']['failed'] > 0) {
            echo "  ⚠ Failed: {$report['matches']['failed']}\n";
        }
    } else {
        echo "  - No match files found\n";
    }

    echo "\n";

    // ==========================================
    // 3. Import Scheduled Matches
    // ==========================================
    echo "[3/4] Importing scheduled matches...\n";

    $scheduledFile = $dataDir . '/scheduled-matches.json';
    if (file_exists($scheduledFile)) {
        $result = Common::safeJsonRead($scheduledFile);

        if ($result['ok']) {
            $scheduled = $result['data'];

            if (is_array($scheduled) && !empty($scheduled)) {
                foreach ($scheduled as $matchId => $match) {
                    try {
                        // Use the ID from the match object if available, otherwise use array key
                        $id = $match['id'] ?? $matchId;

                        $db->insert('scheduled_matches', [
                            'id' => $id,
                            'scheduled_date' => $match['scheduled_date'] ?? date('Y-m-d'),
                            'scheduled_time' => $match['scheduled_time'] ?? '00:00',
                            'match_name' => $match['match_name'] ?? 'Untitled Match',
                            'players' => json_encode($match['players'] ?? []),
                            'team_a' => json_encode($match['teamA'] ?? []),
                            'team_b' => json_encode($match['teamB'] ?? []),
                            'match_format' => $match['matchFormat'] ?? 'limited',
                            'overs_per_innings' => $match['oversPerInnings'] ?? null,
                            'wickets_limit' => $match['wicketsLimit'] ?? null,
                            'toss_winner' => $match['tossWinner'] ?? null,
                            'toss_decision' => $match['tossDecision'] ?? null,
                            'opening_bat1' => $match['openingBat1'] ?? null,
                            'opening_bat2' => $match['openingBat2'] ?? null,
                            'opening_bowler' => $match['openingBowler'] ?? null,
                            'match_id' => $match['match_id'] ?? null,
                            'status' => $match['status'] ?? 'scheduled',
                            'created_at' => $match['created_at'] ?? time(),
                            'created_by' => $match['created_by'] ?? 'admin',
                            'updated_at' => $match['updated_at'] ?? null
                        ]);

                        $report['scheduled_matches']['success']++;

                    } catch (Exception $e) {
                        $report['scheduled_matches']['failed']++;
                        $report['scheduled_matches']['errors'][] = "Scheduled match {$matchId}: " . $e->getMessage();
                    }
                }

                echo "  ✓ Imported {$report['scheduled_matches']['success']} scheduled matches\n";
                if ($report['scheduled_matches']['failed'] > 0) {
                    echo "  ⚠ Failed: {$report['scheduled_matches']['failed']}\n";
                }
            } else {
                echo "  - No scheduled matches found in file\n";
            }
        } else {
            echo "  ⚠ Failed to read scheduled-matches.json: {$result['error']}\n";
        }
    } else {
        echo "  - scheduled-matches.json not found (skipping)\n";
    }

    echo "\n";

    // ==========================================
    // 4. Import Live Sessions
    // ==========================================
    echo "[4/4] Importing live sessions...\n";

    $liveDir = $dataDir . '/live';
    if (is_dir($liveDir)) {
        $liveFiles = glob($liveDir . '/*.json');

        if ($liveFiles !== false && !empty($liveFiles)) {
            foreach ($liveFiles as $file) {
                $liveId = basename($file, '.json');
                $result = Common::safeJsonRead($file);

                if ($result['ok']) {
                    $session = $result['data'];

                    try {
                        $db->insert('live_sessions', [
                            'live_id' => $session['live_id'] ?? $liveId,
                            'match_id' => $session['match_id'] ?? '',
                            'scheduled_match_id' => $session['scheduled_match_id'] ?? null,
                            'created_at' => $session['created_at'] ?? time(),
                            'owner_session' => $session['owner_session'] ?? '',
                            'active' => isset($session['active']) && $session['active'] ? 1 : 0,
                            'current_state' => json_encode($session['current_state'] ?? []),
                            'last_updated' => $session['last_updated'] ?? time(),
                            'stopped_at' => $session['stopped_at'] ?? null,
                            'stopped_by' => $session['stopped_by'] ?? null
                        ]);

                        $report['live_sessions']['success']++;

                    } catch (Exception $e) {
                        $report['live_sessions']['failed']++;
                        $report['live_sessions']['errors'][] = "Live session {$liveId}: " . $e->getMessage();
                    }
                } else {
                    $report['live_sessions']['failed']++;
                    $report['live_sessions']['errors'][] = "Live session {$liveId}: Failed to read file - {$result['error']}";
                }
            }

            echo "  ✓ Imported {$report['live_sessions']['success']} live sessions\n";
            if ($report['live_sessions']['failed'] > 0) {
                echo "  ⚠ Failed: {$report['live_sessions']['failed']}\n";
            }
        } else {
            echo "  - No live session files found\n";
        }
    } else {
        echo "  - live/ directory not found (skipping)\n";
    }

    echo "\n";

    // ==========================================
    // Commit Transaction
    // ==========================================
    $db->commit();

    echo "===========================================\n";
    echo "✓ Data Import Complete!\n";
    echo "===========================================\n\n";

    // ==========================================
    // Print Summary Report
    // ==========================================
    echo "Import Summary:\n";
    echo "---------------\n";
    echo sprintf("Players:           %3d succeeded, %3d failed, %3d skipped\n",
        $report['players']['success'],
        $report['players']['failed'],
        $report['players']['skipped']
    );
    echo sprintf("Matches:           %3d succeeded, %3d failed\n",
        $report['matches']['success'],
        $report['matches']['failed']
    );
    echo sprintf("Scheduled Matches: %3d succeeded, %3d failed\n",
        $report['scheduled_matches']['success'],
        $report['scheduled_matches']['failed']
    );
    echo sprintf("Live Sessions:     %3d succeeded, %3d failed\n",
        $report['live_sessions']['success'],
        $report['live_sessions']['failed']
    );

    echo "\n";

    // Show database stats
    $stats = $db->getStats();
    echo "Database Statistics:\n";
    echo "--------------------\n";
    echo "File size: {$stats['file_size_mb']} MB\n";
    echo "Tables: {$stats['table_count']}\n";
    if (isset($stats['row_counts'])) {
        echo "\nRow counts:\n";
        foreach ($stats['row_counts'] as $table => $count) {
            echo sprintf("  %-20s %s\n", $table . ':', $count);
        }
    }

    // Show errors if any
    $totalErrors = 0;
    foreach ($report as $type => $data) {
        $totalErrors += count($data['errors']);
    }

    if ($totalErrors > 0) {
        echo "\n⚠ Errors encountered during import:\n";
        echo "------------------------------------\n";

        foreach ($report as $type => $data) {
            if (!empty($data['errors'])) {
                echo "\n$type:\n";
                foreach ($data['errors'] as $error) {
                    echo "  - $error\n";
                }
            }
        }
    }

    echo "\n===========================================\n";
    echo "Next Steps:\n";
    echo "  1. Review the import report above\n";
    echo "  2. Verify data integrity in the database\n";
    echo "  3. Test API endpoints with database\n";
    echo "  4. Keep JSON files as backup for 30 days\n";
    echo "===========================================\n\n";

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollback();
    }

    echo "\n✗ Data Import FAILED!\n";
    echo "  Error: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";

    echo "The database has been rolled back to its previous state.\n";
    echo "No data was imported.\n\n";

    exit(1);
}
