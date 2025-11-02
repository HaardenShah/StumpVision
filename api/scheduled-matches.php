<?php
declare(strict_types=1);
session_start();

/**
 * StumpVision — api/scheduled-matches.php
 * Match Scheduling API for pre-planning matches
 */

// Check if user is admin
function isAdmin(): bool
{
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Security headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

$scheduledMatchesFile = __DIR__ . '/../data/scheduled-matches.json';

/**
 * Load scheduled matches from file
 */
function loadScheduledMatches(): array
{
    global $scheduledMatchesFile;
    if (!is_file($scheduledMatchesFile)) {
        return [];
    }
    $content = file_get_contents($scheduledMatchesFile);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

/**
 * Save scheduled matches to file
 */
function saveScheduledMatches(array $matches): bool
{
    global $scheduledMatchesFile;
    $dir = dirname($scheduledMatchesFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return file_put_contents($scheduledMatchesFile, json_encode($matches, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Generate a unique short match ID
 * Format: 6-digit number (e.g., "123456")
 */
function generateMatchId(array $existingMatches): string
{
    $maxAttempts = 100;
    for ($i = 0; $i < $maxAttempts; $i++) {
        $id = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        if (!isset($existingMatches[$id])) {
            return $id;
        }
    }

    // Fallback: use timestamp
    return substr((string)time(), -6);
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    // GET: Get specific match by ID (public - for match day lookup)
    if ($action === 'get' && $method === 'GET') {
        $matchId = $_GET['id'] ?? '';
        if (empty($matchId)) {
            echo json_encode(['ok' => false, 'err' => 'missing_id']);
            exit;
        }

        $matches = loadScheduledMatches();
        if (isset($matches[$matchId])) {
            echo json_encode(['ok' => true, 'match' => $matches[$matchId]]);
        } else {
            echo json_encode(['ok' => false, 'err' => 'not_found']);
        }
        exit;
    }

    // Admin-only actions below
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'err' => 'unauthorized']);
        exit;
    }

    // LIST: Get all scheduled matches
    if ($action === 'list' && $method === 'GET') {
        $matches = loadScheduledMatches();

        // Convert to array and sort by date (newest first)
        $matchesArray = array_values($matches);
        usort($matchesArray, function($a, $b) {
            return ($b['scheduled_date'] ?? 0) <=> ($a['scheduled_date'] ?? 0);
        });

        echo json_encode(['ok' => true, 'matches' => $matchesArray]);
        exit;
    }

    // CREATE: Create new scheduled match
    if ($action === 'create' && $method === 'POST') {
        $raw = file_get_contents('php://input');
        $in = json_decode($raw, true);

        if (!is_array($in)) {
            echo json_encode(['ok' => false, 'err' => 'invalid_input']);
            exit;
        }

        $matches = loadScheduledMatches();
        $matchId = generateMatchId($matches);

        $matches[$matchId] = [
            'id' => $matchId,
            'scheduled_date' => $in['scheduled_date'] ?? date('Y-m-d'),
            'match_name' => trim($in['match_name'] ?? ''),
            'players' => $in['players'] ?? [],
            'teamA' => $in['teamA'] ?? ['name' => 'Team A', 'players' => []],
            'teamB' => $in['teamB'] ?? ['name' => 'Team B', 'players' => []],
            'matchFormat' => $in['matchFormat'] ?? 'limited',
            'oversPerInnings' => (int)($in['oversPerInnings'] ?? 20),
            'wicketsLimit' => (int)($in['wicketsLimit'] ?? 10),
            'tossWinner' => $in['tossWinner'] ?? null,
            'tossDecision' => $in['tossDecision'] ?? null,
            'openingBat1' => $in['openingBat1'] ?? null,
            'openingBat2' => $in['openingBat2'] ?? null,
            'openingBowler' => $in['openingBowler'] ?? null,
            'status' => 'scheduled',
            'created_at' => time(),
            'created_by' => $_SESSION['admin_username'] ?? 'admin'
        ];

        if (saveScheduledMatches($matches)) {
            echo json_encode(['ok' => true, 'match' => $matches[$matchId]]);
        } else {
            echo json_encode(['ok' => false, 'err' => 'save_failed']);
        }
        exit;
    }

    // UPDATE: Update match details
    if ($action === 'update' && $method === 'POST') {
        $raw = file_get_contents('php://input');
        $in = json_decode($raw, true);

        if (!is_array($in) || empty($in['id'])) {
            echo json_encode(['ok' => false, 'err' => 'invalid_input']);
            exit;
        }

        $matches = loadScheduledMatches();
        $matchId = $in['id'];

        if (!isset($matches[$matchId])) {
            echo json_encode(['ok' => false, 'err' => 'not_found']);
            exit;
        }

        // Update allowed fields
        $updateFields = [
            'scheduled_date', 'match_name', 'players',
            'teamA', 'teamB', 'matchFormat', 'oversPerInnings', 'wicketsLimit',
            'tossWinner', 'tossDecision', 'openingBat1', 'openingBat2', 'openingBowler',
            'status'
        ];

        foreach ($updateFields as $field) {
            if (isset($in[$field])) {
                $matches[$matchId][$field] = $in[$field];
            }
        }

        $matches[$matchId]['updated_at'] = time();

        if (saveScheduledMatches($matches)) {
            echo json_encode(['ok' => true, 'match' => $matches[$matchId]]);
        } else {
            echo json_encode(['ok' => false, 'err' => 'save_failed']);
        }
        exit;
    }

    // DELETE: Remove match
    if ($action === 'delete' && $method === 'POST') {
        $raw = file_get_contents('php://input');
        $in = json_decode($raw, true);

        if (!is_array($in) || empty($in['id'])) {
            echo json_encode(['ok' => false, 'err' => 'invalid_input']);
            exit;
        }

        $matches = loadScheduledMatches();
        $matchId = $in['id'];

        if (!isset($matches[$matchId])) {
            echo json_encode(['ok' => false, 'err' => 'not_found']);
            exit;
        }

        unset($matches[$matchId]);

        if (saveScheduledMatches($matches)) {
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'err' => 'save_failed']);
        }
        exit;
    }

    // Unknown action
    http_response_code(400);
    echo json_encode(['ok' => false, 'err' => 'bad_action']);

} catch (\Throwable $e) {
    error_log('StumpVision Scheduled Matches API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => 'server_error']);
}
