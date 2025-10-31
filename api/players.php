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
        $playerId = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $in['name']));

        if (isset($players[$playerId])) {
            echo json_encode(['ok' => false, 'err' => 'player_exists']);
            exit;
        }

        $players[$playerId] = [
            'id' => $playerId,
            'name' => trim($in['name']),
            'team' => trim($in['team'] ?? ''),
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

    // Unknown action
    http_response_code(400);
    echo json_encode(['ok' => false, 'err' => 'bad_action']);

} catch (\Throwable $e) {
    error_log('StumpVision Players API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => 'server_error']);
}
