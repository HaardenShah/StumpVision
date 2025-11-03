<?php
declare(strict_types=1);
session_start();

/**
 * StumpVision — api/live.php
 * Live score sharing API (Database version)
 */

// Load configuration
require_once __DIR__ . '/../admin/config-helper.php';
require_once __DIR__ . '/lib/Common.php';
require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/repositories/LiveSessionRepository.php';

use StumpVision\Common;
use StumpVision\Repositories\LiveSessionRepository;

// Send security headers (SAMEORIGIN to allow embedding in iframes for live view)
Common::sendSecurityHeaders('SAMEORIGIN');

$repo = new LiveSessionRepository();
$action = $_GET['action'] ?? '';

// Check if live score sharing is enabled ONLY for public viewing
// Allow create/update for state persistence even when public sharing is disabled
if (!Config::isLiveScoreEnabled() && $action === 'get') {
    // Only block public GET requests when live sharing is disabled
    $liveId = $_GET['live_id'] ?? '';
    $session = $repo->findById($liveId);

    // Allow the owner to always access their own session
    if (!$session || $session['owner_session'] !== session_id()) {
        Common::jsonResponse(false, null, 'live_score_disabled', 403);
    }
}

// Check rate limit (120 requests per minute for live updates - higher than normal API)
if (!Common::checkRateLimit(120, 'live_rate_limit')) {
    Common::jsonResponse(false, null, 'rate_limit_exceeded', 429);
}

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
        $success = $repo->create(
            $liveId,
            $in['match_id'],
            session_id(),
            [],  // Empty state initially
            $in['scheduled_match_id'] ?? null
        );

        if (!$success) {
            Common::jsonResponse(false, null, 'create_failed');
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

        // Check if session exists
        if (!$repo->exists($liveId)) {
            Common::jsonResponse(false, null, 'session_not_found');
        }

        // Verify ownership
        if (!$repo->isOwnedBySession($liveId, session_id())) {
            Common::jsonResponse(false, null, 'unauthorized', 403);
        }

        // Update state
        if ($repo->updateState($liveId, $in['state']) === 0) {
            Common::jsonResponse(false, null, 'update_failed');
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
        $session = $repo->findById($safeId);

        if (!$session) {
            Common::jsonResponse(false, null, 'session_not_found');
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

        // Check if session exists
        if (!$repo->exists($liveId)) {
            Common::jsonResponse(false, null, 'session_not_found');
        }

        // Verify ownership
        if (!$repo->isOwnedBySession($liveId, session_id())) {
            Common::jsonResponse(false, null, 'unauthorized', 403);
        }

        // Stop the session
        if ($repo->stop($liveId) === 0) {
            Common::jsonResponse(false, null, 'stop_failed');
        }

        Common::jsonResponse(true);
    }

    // Unknown action
    Common::jsonResponse(false, null, 'bad_action', 400);

} catch (\Throwable $e) {
    error_log('StumpVision Live API Error: ' . $e->getMessage());
    Common::jsonResponse(false, null, 'server_error', 500);
}
