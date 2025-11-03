<?php
declare(strict_types=1);

/**
 * StumpVision — api/players.php
 * Player Registry API (Database version)
 */

require_once __DIR__ . '/lib/SessionConfig.php';
require_once __DIR__ . '/lib/Common.php';
require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/repositories/PlayerRepository.php';

use StumpVision\Common;
use StumpVision\Repositories\PlayerRepository;

// Send security headers
Common::sendSecurityHeaders('DENY');

$repo = new PlayerRepository();

/**
 * Generate a unique player code from name
 * Format: First 2 letters of first name + First 2 letters of last name + 4 digit number
 * Example: "John Smith" -> "JOSM-1234"
 */
function generatePlayerCode(string $name, PlayerRepository $repo): string
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

        // Check if code is unique using repository
        if ($repo->isCodeUnique($code)) {
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
        $players = $repo->findAll();
        Common::jsonResponse(true, ['players' => array_values($players)]);
    }

    // GET: Get specific player (public)
    if ($action === 'get' && $method === 'GET') {
        $playerId = $_GET['id'] ?? '';
        if (empty($playerId)) {
            Common::jsonResponse(false, null, 'missing_id');
        }

        $player = $repo->findById($playerId);
        if ($player) {
            Common::jsonResponse(true, ['player' => $player]);
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

        $code = strtoupper(trim($in['code']));

        // Find player by code
        $found = $repo->findByCode($code);

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

        $results = $repo->searchByName($query);

        // Format results to match API contract
        $formattedResults = [];
        foreach ($results as $player) {
            $formattedResults[] = [
                'id' => $player['id'],
                'name' => $player['name'],
                'code' => $player['code'],
                'team' => $player['team'] ?? ''
            ];
        }

        Common::jsonResponse(true, ['players' => $formattedResults]);
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

        // Generate UUID for new player
        $playerId = generateUUID();

        // Check if name already exists (warn but prevent)
        $nameSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $in['name']));
        $allPlayers = $repo->findAll();
        $nameExists = false;

        foreach ($allPlayers as $p) {
            if (strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $p['name'])) === $nameSlug) {
                $nameExists = true;
                break;
            }
        }

        if ($nameExists) {
            Common::jsonResponse(false, null, 'player_name_exists');
        }

        // Generate unique player code
        $playerCode = generatePlayerCode($in['name'], $repo);

        // Create player data
        $playerData = [
            'id' => $playerId,
            'name' => trim($in['name']),
            'code' => $playerCode,
            'team' => trim($in['team'] ?? ''),
            'player_type' => trim($in['player_type'] ?? ''),
            'registered_by' => $_SESSION['admin_username'] ?? 'admin'
        ];

        if ($repo->create($playerData)) {
            // Return the created player
            $createdPlayer = $repo->findById($playerId);
            Common::jsonResponse(true, ['player' => $createdPlayer]);
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

        $playerId = $in['id'];

        if (!$repo->exists($playerId)) {
            Common::jsonResponse(false, null, 'not_found');
        }

        // Prepare update data
        $updateData = [];
        if (isset($in['name'])) $updateData['name'] = trim($in['name']);
        if (isset($in['team'])) $updateData['team'] = trim($in['team']);
        if (isset($in['player_type'])) $updateData['player_type'] = trim($in['player_type']);

        if ($repo->update($playerId, $updateData) > 0) {
            $updatedPlayer = $repo->findById($playerId);
            Common::jsonResponse(true, ['player' => $updatedPlayer]);
        } else {
            Common::jsonResponse(false, null, 'save_failed');
        }
    }

    // DELETE: Remove player (soft delete)
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

        $playerId = $in['id'];

        if (!$repo->exists($playerId)) {
            Common::jsonResponse(false, null, 'not_found');
        }

        if ($repo->delete($playerId) > 0) {
            Common::jsonResponse(true);
        } else {
            Common::jsonResponse(false, null, 'delete_failed');
        }
    }

    // Unknown action
    Common::jsonResponse(false, null, 'bad_action', 400);

} catch (\Throwable $e) {
    error_log('StumpVision Players API Error: ' . $e->getMessage());
    Common::jsonResponse(false, null, 'server_error', 500);
}
