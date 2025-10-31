<?php
declare(strict_types=1);
session_start();

/**
 * StumpVision — api/live.php
 * Live score sharing API (disabled by default)
 */

// Configuration - set to true to enable live score sharing
define('LIVE_SCORE_ENABLED', false);

// Security headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

if (!LIVE_SCORE_ENABLED) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'err' => 'live_score_disabled']);
    exit;
}

$dataDir = __DIR__ . '/../data/live';

// Ensure live data directory exists
if (!is_dir($dataDir)) {
    if (!@mkdir($dataDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'err' => 'Cannot create live data directory']);
        exit;
    }
}

/**
 * Sanitize live match ID
 */
function safe_live_id(string $id): string
{
    $id = basename($id);
    $id = str_replace(['..', '/', '\\'], '', $id);
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id);
    return substr($id, 0, 64);
}

/**
 * Get file path for live match ID
 */
function live_path_for(string $id): string
{
    global $dataDir;
    return $dataDir . DIRECTORY_SEPARATOR . safe_live_id($id) . '.json';
}

/**
 * Simple rate limiting
 */
function check_live_rate_limit(): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'live_rate_limit_' . md5($ip);

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'reset' => time() + 60];
    }

    $data = $_SESSION[$key];

    if (time() >= $data['reset']) {
        $_SESSION[$key] = ['count' => 1, 'reset' => time() + 60];
        return true;
    }

    if ($data['count'] >= 120) {
        return false;
    }

    $_SESSION[$key]['count']++;
    return true;
}

// Check rate limit
if (!check_live_rate_limit()) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'err' => 'rate_limit_exceeded']);
    exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    // CREATE: Start live sharing for a match
    if ($action === 'create' && $method === 'POST') {
        $raw = file_get_contents('php://input');

        if ($raw === false || empty($raw)) {
            echo json_encode(['ok' => false, 'err' => 'empty_request']);
            exit;
        }

        $in = json_decode($raw, true);

        if (!is_array($in) || !isset($in['match_id'])) {
            echo json_encode(['ok' => false, 'err' => 'bad_payload']);
            exit;
        }

        // Generate unique live ID
        $liveId = bin2hex(random_bytes(8));

        // Create live session
        $liveSession = [
            'live_id' => $liveId,
            'match_id' => $in['match_id'],
            'created_at' => time(),
            'owner_session' => session_id(),
            'active' => true,
            'current_state' => null
        ];

        $f = live_path_for($liveId);
        if (file_put_contents($f, json_encode($liveSession, JSON_PRETTY_PRINT)) === false) {
            echo json_encode(['ok' => false, 'err' => 'write_error']);
            exit;
        }

        echo json_encode(['ok' => true, 'live_id' => $liveId]);
        exit;
    }

    // UPDATE: Push live score update
    if ($action === 'update' && $method === 'POST') {
        $raw = file_get_contents('php://input');

        if ($raw === false || empty($raw)) {
            echo json_encode(['ok' => false, 'err' => 'empty_request']);
            exit;
        }

        $in = json_decode($raw, true);

        if (!is_array($in) || !isset($in['live_id']) || !isset($in['state'])) {
            echo json_encode(['ok' => false, 'err' => 'bad_payload']);
            exit;
        }

        $liveId = safe_live_id($in['live_id']);
        $f = live_path_for($liveId);

        if (!is_file($f)) {
            echo json_encode(['ok' => false, 'err' => 'not_found']);
            exit;
        }

        $session = json_decode(file_get_contents($f), true);

        // Verify ownership
        if ($session['owner_session'] !== session_id()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'err' => 'unauthorized']);
            exit;
        }

        // Update state
        $session['current_state'] = $in['state'];
        $session['last_updated'] = time();

        if (file_put_contents($f, json_encode($session, JSON_PRETTY_PRINT)) === false) {
            echo json_encode(['ok' => false, 'err' => 'write_error']);
            exit;
        }

        echo json_encode(['ok' => true]);
        exit;
    }

    // GET: Retrieve current live score
    if ($action === 'get' && $method === 'GET') {
        $liveId = $_GET['live_id'] ?? '';

        if (empty($liveId)) {
            echo json_encode(['ok' => false, 'err' => 'missing_id']);
            exit;
        }

        $safeId = safe_live_id($liveId);
        $f = live_path_for($safeId);

        if (!is_file($f)) {
            echo json_encode(['ok' => false, 'err' => 'not_found']);
            exit;
        }

        $session = json_decode(file_get_contents($f), true);

        if (!$session['active']) {
            echo json_encode(['ok' => false, 'err' => 'session_inactive']);
            exit;
        }

        // Return current state
        echo json_encode([
            'ok' => true,
            'state' => $session['current_state'] ?? null,
            'last_updated' => $session['last_updated'] ?? $session['created_at']
        ]);
        exit;
    }

    // STOP: Deactivate live sharing
    if ($action === 'stop' && $method === 'POST') {
        $raw = file_get_contents('php://input');
        $in = json_decode($raw, true);

        if (!is_array($in) || !isset($in['live_id'])) {
            echo json_encode(['ok' => false, 'err' => 'bad_payload']);
            exit;
        }

        $liveId = safe_live_id($in['live_id']);
        $f = live_path_for($liveId);

        if (!is_file($f)) {
            echo json_encode(['ok' => false, 'err' => 'not_found']);
            exit;
        }

        $session = json_decode(file_get_contents($f), true);

        // Verify ownership
        if ($session['owner_session'] !== session_id()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'err' => 'unauthorized']);
            exit;
        }

        $session['active'] = false;
        file_put_contents($f, json_encode($session, JSON_PRETTY_PRINT));

        echo json_encode(['ok' => true]);
        exit;
    }

    // Unknown action
    http_response_code(400);
    echo json_encode(['ok' => false, 'err' => 'bad_action']);

} catch (\Throwable $e) {
    error_log('StumpVision Live API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => 'server_error']);
}
