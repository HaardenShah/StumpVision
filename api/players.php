<?php
declare(strict_types=1);
session_start();

/**
 * StumpVision — api/players.php
 * Player Registry API
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

$playersFile = __DIR__ . '/../data/players.json';

/**
 * Load players from file
 */
function loadPlayers(): array
{
    global $playersFile;
    if (!is_file($playersFile)) {
        return [];
    }
    $content = file_get_contents($playersFile);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

/**
 * Save players to file
 */
function savePlayers(array $players): bool
{
    global $playersFile;
    $dir = dirname($playersFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return file_put_contents($playersFile, json_encode($players, JSON_PRETTY_PRINT)) !== false;
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

/**
 * Verify player code matches player ID
 */
function verifyPlayerCode(string $playerId, string $code): bool
{
    $players = loadPlayers();
    if (!isset($players[$playerId])) {
        return false;
    }
    return ($players[$playerId]['code'] ?? '') === strtoupper(trim($code));
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    // LIST: Get all registered players (public)
    if ($action === 'list' && $method === 'GET') {
        $players = loadPlayers();
        echo json_encode(['ok' => true, 'players' => array_values($players)]);
        exit;
    }

    // GET: Get specific player (public)
    if ($action === 'get' && $method === 'GET') {
        $playerId = $_GET['id'] ?? '';
        if (empty($playerId)) {
            echo json_encode(['ok' => false, 'err' => 'missing_id']);
            exit;
        }

        $players = loadPlayers();
        if (isset($players[$playerId])) {
            echo json_encode(['ok' => true, 'player' => $players[$playerId]]);
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

    // ADD: Register new player
    if ($action === 'add' && $method === 'POST') {
        $raw = file_get_contents('php://input');
        $in = json_decode($raw, true);

        if (!is_array($in) || empty($in['name'])) {
            echo json_encode(['ok' => false, 'err' => 'invalid_input']);
            exit;
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
            echo json_encode(['ok' => false, 'err' => 'player_name_exists']);
            exit;
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
            echo json_encode(['ok' => true, 'player' => $players[$playerId]]);
        } else {
            echo json_encode(['ok' => false, 'err' => 'save_failed']);
        }
        exit;
    }

    // UPDATE: Update player info
    if ($action === 'update' && $method === 'POST') {
        $raw = file_get_contents('php://input');
        $in = json_decode($raw, true);

        if (!is_array($in) || empty($in['id'])) {
            echo json_encode(['ok' => false, 'err' => 'invalid_input']);
            exit;
        }

        $players = loadPlayers();
        $playerId = $in['id'];

        if (!isset($players[$playerId])) {
            echo json_encode(['ok' => false, 'err' => 'not_found']);
            exit;
        }

        if (isset($in['name'])) $players[$playerId]['name'] = trim($in['name']);
        if (isset($in['team'])) $players[$playerId]['team'] = trim($in['team']);
        if (isset($in['player_type'])) $players[$playerId]['player_type'] = trim($in['player_type']);
        $players[$playerId]['updated_at'] = time();

        if (savePlayers($players)) {
            echo json_encode(['ok' => true, 'player' => $players[$playerId]]);
        } else {
            echo json_encode(['ok' => false, 'err' => 'save_failed']);
        }
        exit;
    }

    // DELETE: Remove player
    if ($action === 'delete' && $method === 'POST') {
        $raw = file_get_contents('php://input');
        $in = json_decode($raw, true);

        if (!is_array($in) || empty($in['id'])) {
            echo json_encode(['ok' => false, 'err' => 'invalid_input']);
            exit;
        }

        $players = loadPlayers();
        $playerId = $in['id'];

        if (!isset($players[$playerId])) {
            echo json_encode(['ok' => false, 'err' => 'not_found']);
            exit;
        }

        unset($players[$playerId]);

        if (savePlayers($players)) {
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'err' => 'save_failed']);
        }
        exit;
    }

    // VERIFY: Verify player code (public)
    if ($action === 'verify' && $method === 'POST') {
        $raw = file_get_contents('php://input');
        $in = json_decode($raw, true);

        if (!is_array($in) || empty($in['name']) || empty($in['code'])) {
            echo json_encode(['ok' => false, 'err' => 'invalid_input']);
            exit;
        }

        $players = loadPlayers();
        $code = strtoupper(trim($in['code']));
        $name = trim($in['name']);

        // Debug logging - remove after testing
        error_log("Player verification attempt - Code: {$code}, Name: {$name}");
        error_log("Total players in DB: " . count($players));

        // Find player by code
        $found = null;
        foreach ($players as $player) {
            error_log("Checking player code: " . ($player['code'] ?? 'NO_CODE') . " against: {$code}");
            if (($player['code'] ?? '') === $code) {
                $found = $player;
                error_log("Match found for code: {$code}");
                break;
            }
        }

        if ($found) {
            // Return player info for verification
            echo json_encode([
                'ok' => true,
                'verified' => true,
                'player' => [
                    'id' => $found['id'],
                    'name' => $found['name'],
                    'team' => $found['team'] ?? '',
                    'code' => $found['code']
                ]
            ]);
        } else {
            error_log("No match found for code: {$code}");
            echo json_encode(['ok' => true, 'verified' => false]);
        }
        exit;
    }

    // SEARCH: Search players by name (public)
    if ($action === 'search' && $method === 'GET') {
        $query = strtolower(trim($_GET['q'] ?? ''));
        if (empty($query)) {
            echo json_encode(['ok' => true, 'players' => []]);
            exit;
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
                    'team' => $player['team'] ?? '',
                    'code' => $player['code'] ?? ''
                ];
            }
        }

        // Limit results
        $results = array_slice($results, 0, 10);

        echo json_encode(['ok' => true, 'players' => $results]);
        exit;
    }

    // Unknown action
    http_response_code(400);
    echo json_encode(['ok' => false, 'err' => 'bad_action']);

} catch (\Throwable $e) {
    error_log('StumpVision Players API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => 'server_error']);
}
