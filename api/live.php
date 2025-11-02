<?php
declare(strict_types=1);
session_start();

/**
 * StumpVision — api/live.php
 * Live score sharing API (configurable via admin settings)
 */

// Load configuration
require_once __DIR__ . '/../admin/config-helper.php';
require_once __DIR__ . '/lib/Common.php';

use StumpVision\Common;

// Send security headers (SAMEORIGIN to allow embedding in iframes for live view)
Common::sendSecurityHeaders('SAMEORIGIN');

// Check if live score sharing is enabled via admin settings
if (!Config::isLiveScoreEnabled()) {
    Common::jsonResponse(false, null, 'live_score_disabled', 403);
}

$dataDir = __DIR__ . '/../data/live';

// Ensure live data directory exists
if (!Common::ensureDirectory($dataDir)) {
    Common::jsonResponse(false, null, 'Cannot create live data directory', 500);
}

/**
 * Get file path for live match ID
 */
function live_path_for(string $id): string
{
    global $dataDir;
    return $dataDir . DIRECTORY_SEPARATOR . Common::sanitizeId($id) . '.json';
}

// Check rate limit (120 requests per minute for live updates - higher than normal API)
if (!Common::checkRateLimit(120, 'live_rate_limit')) {
    Common::jsonResponse(false, null, 'rate_limit_exceeded', 429);
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    // CREATE: Start live sharing for a match
    if ($action === 'create' && $method === 'POST') {
        $raw = file_get_contents('php://input');

        if ($raw === false || empty($raw)) {
            Common::jsonResponse(false, null, 'empty_request');
        }

        $in = json_decode($raw, true);

        if (!is_array($in) || !isset($in['match_id'])) {
            Common::jsonResponse(false, null, 'bad_payload');
        }

        // Generate unique live ID
        $liveId = bin2hex(random_bytes(8));

        // Create live session
        $liveSession = [
            'live_id' => $liveId,
            'match_id' => $in['match_id'],
            'scheduled_match_id' => $in['scheduled_match_id'] ?? null,
            'created_at' => time(),
            'owner_session' => session_id(),
            'active' => true,
            'current_state' => null
        ];

        $f = live_path_for($liveId);
        if (!Common::safeJsonWrite($f, $liveSession)) {
            Common::jsonResponse(false, null, 'write_error');
        }

        Common::jsonResponse(true, ['live_id' => $liveId]);
    }

    // UPDATE: Push live score update
    if ($action === 'update' && $method === 'POST') {
        $raw = file_get_contents('php://input');

        if ($raw === false || empty($raw)) {
            Common::jsonResponse(false, null, 'empty_request');
        }

        $in = json_decode($raw, true);

        if (!is_array($in) || !isset($in['live_id']) || !isset($in['state'])) {
            Common::jsonResponse(false, null, 'bad_payload');
        }

        $liveId = Common::sanitizeId($in['live_id']);
        $f = live_path_for($liveId);

        $result = Common::safeJsonRead($f);
        if (!$result['ok']) {
            Common::jsonResponse(false, null, $result['error']);
        }

        $session = $result['data'];
        if (!is_array($session)) {
            Common::jsonResponse(false, null, 'invalid_session');
        }

        // Verify ownership
        if ($session['owner_session'] !== session_id()) {
            Common::jsonResponse(false, null, 'unauthorized', 403);
        }

        // Update state
        $session['current_state'] = $in['state'];
        $session['last_updated'] = time();

        if (!Common::safeJsonWrite($f, $session)) {
            Common::jsonResponse(false, null, 'write_error');
        }

        Common::jsonResponse(true);
    }

    // GET: Retrieve current live score
    if ($action === 'get' && $method === 'GET') {
        $liveId = $_GET['live_id'] ?? '';

        if (empty($liveId)) {
            Common::jsonResponse(false, null, 'missing_id');
        }

        $safeId = Common::sanitizeId($liveId);
        $f = live_path_for($safeId);

        $result = Common::safeJsonRead($f);
        if (!$result['ok']) {
            Common::jsonResponse(false, null, $result['error']);
        }

        $session = $result['data'];
        if (!is_array($session)) {
            Common::jsonResponse(false, null, 'invalid_session');
        }

        if (!$session['active']) {
            Common::jsonResponse(false, null, 'session_inactive');
        }

        // Return current state
        Common::jsonResponse(true, [
            'state' => $session['current_state'] ?? null,
            'last_updated' => $session['last_updated'] ?? $session['created_at']
        ]);
    }

    // STOP: Deactivate live sharing
    if ($action === 'stop' && $method === 'POST') {
        $raw = file_get_contents('php://input');
        $in = json_decode($raw, true);

        if (!is_array($in) || !isset($in['live_id'])) {
            Common::jsonResponse(false, null, 'bad_payload');
        }

        $liveId = Common::sanitizeId($in['live_id']);
        $f = live_path_for($liveId);

        $result = Common::safeJsonRead($f);
        if (!$result['ok']) {
            Common::jsonResponse(false, null, $result['error']);
        }

        $session = $result['data'];
        if (!is_array($session)) {
            Common::jsonResponse(false, null, 'invalid_session');
        }

        // Verify ownership
        if ($session['owner_session'] !== session_id()) {
            Common::jsonResponse(false, null, 'unauthorized', 403);
        }

        $session['active'] = false;
        Common::safeJsonWrite($f, $session);

        Common::jsonResponse(true);
    }

    // Unknown action
    Common::jsonResponse(false, null, 'bad_action', 400);

} catch (\Throwable $e) {
    error_log('StumpVision Live API Error: ' . $e->getMessage());
    Common::jsonResponse(false, null, 'server_error', 500);
}
