<?php
declare(strict_types=1);
session_start();

/**
 * StumpVision — api/scheduled-matches.php
 * Match Scheduling API for pre-planning matches
 */

require_once __DIR__ . '/lib/Common.php';

use StumpVision\Common;

// Send security headers
Common::sendSecurityHeaders('DENY');

$scheduledMatchesFile = __DIR__ . '/../data/scheduled-matches.json';

/**
 * Load scheduled matches from file
 */
function loadScheduledMatches(): array
{
    global $scheduledMatchesFile;

    $result = Common::safeJsonRead($scheduledMatchesFile);
    if (!$result['ok']) {
        return [];
    }

    return is_array($result['data']) ? $result['data'] : [];
}

/**
 * Save scheduled matches to file
 */
function saveScheduledMatches(array $matches): bool
{
    global $scheduledMatchesFile;

    $dir = dirname($scheduledMatchesFile);
    if (!Common::ensureDirectory($dir)) {
        return false;
    }

    return Common::safeJsonWrite($scheduledMatchesFile, $matches);
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
            Common::jsonResponse(false, null, 'missing_id');
        }

        $matches = loadScheduledMatches();
        if (isset($matches[$matchId])) {
            Common::jsonResponse(true, ['match' => $matches[$matchId]]);
        } else {
            Common::jsonResponse(false, null, 'not_found');
        }
    }

    // Admin-only actions below
    if (!Common::isAdmin()) {
        Common::jsonResponse(false, null, 'unauthorized', 403);
    }

    // LIST: Get all scheduled matches
    if ($action === 'list' && $method === 'GET') {
        $matches = loadScheduledMatches();

        // Convert to array and sort by date (newest first)
        $matchesArray = array_values($matches);
        usort($matchesArray, function($a, $b) {
            return ($b['scheduled_date'] ?? 0) <=> ($a['scheduled_date'] ?? 0);
        });

        Common::jsonResponse(true, ['matches' => $matchesArray]);
    }

    // CREATE: Create new scheduled match
    if ($action === 'create' && $method === 'POST') {
        $raw = file_get_contents('php://input');
        $in = json_decode($raw, true);

        if (!is_array($in)) {
            Common::jsonResponse(false, null, 'invalid_input');
        }

        // Validate CSRF token
        $token = $in['csrf_token'] ?? '';
        if (!Common::validateCsrfToken($token, 'admin_csrf_token')) {
            Common::jsonResponse(false, null, 'invalid_csrf_token', 403);
        }

        $matches = loadScheduledMatches();
        $matchId = generateMatchId($matches);

        $matches[$matchId] = [
            'id' => $matchId,
            'scheduled_date' => $in['scheduled_date'] ?? date('Y-m-d'),
            'scheduled_time' => $in['scheduled_time'] ?? '10:00',
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
            Common::jsonResponse(true, ['match' => $matches[$matchId]]);
        } else {
            Common::jsonResponse(false, null, 'save_failed');
        }
    }

    // UPDATE: Update match details
    if ($action === 'update' && $method === 'POST') {
        $raw = file_get_contents('php://input');
        $in = json_decode($raw, true);

        if (!is_array($in) || empty($in['id'])) {
            Common::jsonResponse(false, null, 'invalid_input');
        }

        // Validate CSRF token
        $token = $in['csrf_token'] ?? '';
        if (!Common::validateCsrfToken($token, 'admin_csrf_token')) {
            Common::jsonResponse(false, null, 'invalid_csrf_token', 403);
        }

        $matches = loadScheduledMatches();
        $matchId = $in['id'];

        if (!isset($matches[$matchId])) {
            Common::jsonResponse(false, null, 'not_found');
        }

        // Update allowed fields
        $updateFields = [
            'scheduled_date', 'scheduled_time', 'match_name', 'players',
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
            Common::jsonResponse(true, ['match' => $matches[$matchId]]);
        } else {
            Common::jsonResponse(false, null, 'save_failed');
        }
    }

    // DELETE: Remove match
    if ($action === 'delete' && $method === 'POST') {
        $raw = file_get_contents('php://input');
        $in = json_decode($raw, true);

        if (!is_array($in) || empty($in['id'])) {
            Common::jsonResponse(false, null, 'invalid_input');
        }

        // Validate CSRF token
        $token = $in['csrf_token'] ?? '';
        if (!Common::validateCsrfToken($token, 'admin_csrf_token')) {
            Common::jsonResponse(false, null, 'invalid_csrf_token', 403);
        }

        $matches = loadScheduledMatches();
        $matchId = $in['id'];

        if (!isset($matches[$matchId])) {
            Common::jsonResponse(false, null, 'not_found');
        }

        unset($matches[$matchId]);

        if (saveScheduledMatches($matches)) {
            Common::jsonResponse(true);
        } else {
            Common::jsonResponse(false, null, 'save_failed');
        }
    }

    // Unknown action
    Common::jsonResponse(false, null, 'bad_action', 400);

} catch (\Throwable $e) {
    error_log('StumpVision Scheduled Matches API Error: ' . $e->getMessage());
    Common::jsonResponse(false, null, 'server_error', 500);
}
