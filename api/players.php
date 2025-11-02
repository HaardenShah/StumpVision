<?php
declare(strict_types=1);
session_start();

/**
 * StumpVision — api/players.php
 * Player Registry API
 */

require_once __DIR__ . '/lib/Common.php';

use StumpVision\Common;

// Send security headers
Common::sendSecurityHeaders('DENY');

$playersFile = __DIR__ . '/../data/players.json';

/**
 * Load players from file
 */
function loadPlayers(): array
{
    global $playersFile;

    $result = Common::safeJsonRead($playersFile);
    if (!$result['ok']) {
        return [];
    }

    return is_array($result['data']) ? $result['data'] : [];
}

/**
 * Save players to file
 */
function savePlayers(array $players): bool
{
    global $playersFile;

    $dir = dirname($playersFile);
    if (!Common::ensureDirectory($dir)) {
        return false;
    }

    return Common::safeJsonWrite($playersFile, $players);
}

/**
 * Generate a unique player code from name
 * Format: First 2 letters of first name + First 2 letters of last name + 4 digit number
 * Example: "John Smith" -> "JOSM-1234"
 */
function generatePlayerCode(string $name, array $existingPlayers): string
{
    // Extract initials
    $parts = preg_split('/\s+/', trim($name));
    $firstName = $parts[0] ?? '';
    $lastName = $parts[count($parts) - 1] ?? '';

    // Get first 2 letters of each
    $prefix = strtoupper(
        substr($firstName, 0, 2) .
        substr($lastName, 0, 2)
    );

    // Ensure at least 2 characters
    if (strlen($prefix) < 2) {
        $prefix = strtoupper(substr($name, 0, 4));
    }
    $prefix = str_pad($prefix, 4, 'X');

    // Generate random 4-digit number and check uniqueness
    $maxAttempts = 100;
    for ($i = 0; $i < $maxAttempts; $i++) {
        $number = str_pad((string)random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        $code = $prefix . '-' . $number;

        // Check if code already exists
        $exists = false;
        foreach ($existingPlayers as $player) {
            if (($player['code'] ?? '') === $code) {
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            return $code;
        }
    }

    // Fallback: use timestamp
    return $prefix . '-' . substr((string)time(), -4);
}

/**
 * Generate a UUID v4
 */
function generateUUID(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variant

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    // LIST: Get all registered players (public)
    if ($action === 'list' && $method === 'GET') {
        $players = loadPlayers();
        Common::jsonResponse(true, ['players' => array_values($players)]);
    }

    // GET: Get specific player (public)
    if ($action === 'get' && $method === 'GET') {
        $playerId = $_GET['id'] ?? '';
        if (empty($playerId)) {
            Common::jsonResponse(false, null, 'missing_id');
        }

        $players = loadPlayers();
        if (isset($players[$playerId])) {
            Common::jsonResponse(true, ['player' => $players[$playerId]]);
        } else {
            Common::jsonResponse(false, null, 'not_found');
        }
    }

    // VERIFY: Verify player code (public)
    // Supports both code-only lookup and name+code verification
    if ($action === 'verify' && $method === 'POST') {
        $raw = file_get_contents('php://input');
        $in = json_decode($raw, true);

        if (!is_array($in) || empty($in['code'])) {
            Common::jsonResponse(false, null, 'invalid_input');
        }

        $players = loadPlayers();
        $code = strtoupper(trim($in['code']));

        // Find player by code
        $found = null;
        foreach ($players as $player) {
            if (($player['code'] ?? '') === $code) {
                $found = $player;
                break;
            }
        }

        if ($found) {
            // Return player info for verification
            Common::jsonResponse(true, [
                'verified' => true,
                'player' => [
                    'id' => $found['id'],
                    'name' => $found['name'],
                    'team' => $found['team'] ?? '',
                    'code' => $found['code']
                ]
            ]);
        } else {
            Common::jsonResponse(true, ['verified' => false]);
        }
    }

    // SEARCH: Search players by name (public)
    if ($action === 'search' && $method === 'GET') {
        $query = strtolower(trim($_GET['q'] ?? ''));
        if (empty($query)) {
            Common::jsonResponse(true, ['players' => []]);
        }

        $players = loadPlayers();
        $results = [];

        foreach ($players as $player) {
            $playerName = strtolower($player['name']);
            // Match if query is in player name
            if (strpos($playerName, $query) !== false) {
                $results[] = [
                    'id' => $player['id'],
                    'name' => $player['name'],
                    'code' => $player['code'],
                    'team' => $player['team'] ?? ''
                ];
            }
        }

        Common::jsonResponse(true, ['players' => $results]);
    }

    // Admin-only actions below
    if (!Common::isAdmin()) {
        Common::jsonResponse(false, null, 'unauthorized', 403);
    }

    // ADD: Register new player
    if ($action === 'add' && $method === 'POST') {
        $raw = file_get_contents('php://input');
        $in = json_decode($raw, true);

        if (!is_array($in) || empty($in['name'])) {
            Common::jsonResponse(false, null, 'invalid_input');
        }

        // Validate CSRF token
        $token = $in['csrf_token'] ?? '';
        if (!Common::validateCsrfToken($token, 'admin_csrf_token')) {
            Common::jsonResponse(false, null, 'invalid_csrf_token', 403);
        }

        $players = loadPlayers();

        // Generate UUID for new player
        $playerId = generateUUID();

        // Check if name already exists (warn but allow)
        $nameSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $in['name']));
        $nameExists = false;
        foreach ($players as $p) {
            if (strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $p['name'])) === $nameSlug) {
                $nameExists = true;
                break;
            }
        }

        if ($nameExists) {
            Common::jsonResponse(false, null, 'player_name_exists');
        }

        // Generate unique player code
        $playerCode = generatePlayerCode($in['name'], $players);

        $players[$playerId] = [
            'id' => $playerId,
            'name' => trim($in['name']),
            'code' => $playerCode,
            'team' => trim($in['team'] ?? ''),
            'player_type' => trim($in['player_type'] ?? ''),
            'registered_at' => time(),
            'registered_by' => $_SESSION['admin_username'] ?? 'admin'
        ];

        if (savePlayers($players)) {
            Common::jsonResponse(true, ['player' => $players[$playerId]]);
        } else {
            Common::jsonResponse(false, null, 'save_failed');
        }
    }

    // UPDATE: Update player info
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

        $players = loadPlayers();
        $playerId = $in['id'];

        if (!isset($players[$playerId])) {
            Common::jsonResponse(false, null, 'not_found');
        }

        if (isset($in['name'])) $players[$playerId]['name'] = trim($in['name']);
        if (isset($in['team'])) $players[$playerId]['team'] = trim($in['team']);
        if (isset($in['player_type'])) $players[$playerId]['player_type'] = trim($in['player_type']);
        $players[$playerId]['updated_at'] = time();

        if (savePlayers($players)) {
            Common::jsonResponse(true, ['player' => $players[$playerId]]);
        } else {
            Common::jsonResponse(false, null, 'save_failed');
        }
    }

    // DELETE: Remove player
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

        $players = loadPlayers();
        $playerId = $in['id'];

        if (!isset($players[$playerId])) {
            Common::jsonResponse(false, null, 'not_found');
        }

        unset($players[$playerId]);

        if (savePlayers($players)) {
            Common::jsonResponse(true);
        } else {
            Common::jsonResponse(false, null, 'save_failed');
        }
    }

    // Unknown action
    Common::jsonResponse(false, null, 'bad_action', 400);

} catch (\Throwable $e) {
    error_log('StumpVision Players API Error: ' . $e->getMessage());
    Common::jsonResponse(false, null, 'server_error', 500);
}
