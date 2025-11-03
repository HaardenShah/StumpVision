<?php
declare(strict_types=1);

/**
 * StumpVision — api/matches.php
 * Match data CRUD API (Database version)
 */

require_once __DIR__ . '/lib/SessionConfig.php';
require_once __DIR__ . '/lib/Common.php';
require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/repositories/MatchRepository.php';

use StumpVision\Common;
use StumpVision\Repositories\MatchRepository;

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    Common::jsonResponse(false, null, 'PHP 7.4+ required', 500);
}

// Send security headers
Common::sendSecurityHeaders('DENY');

$repo = new MatchRepository();

/**
 * Validate match payload structure
 */
function validate_payload(array $payload): bool
{
    // Basic structure validation
    if (!isset($payload['meta']) || !is_array($payload['meta'])) {
        return false;
    }
    if (!isset($payload['teams']) || !is_array($payload['teams'])) {
        return false;
    }
    if (!isset($payload['innings']) || !is_array($payload['innings'])) {
        return false;
    }
    return true;
}

// Get request method and action
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Check rate limit for all requests (60 requests per minute)
if (!Common::checkRateLimit(60, 'matches_rate_limit')) {
    Common::jsonResponse(false, null, 'rate_limit_exceeded', 429);
}

try {
    // GET CSRF TOKEN: Return token for client
    if ($action === 'get-token' && $method === 'GET') {
        Common::jsonResponse(true, ['token' => Common::getCsrfToken()]);
    }

    // LIST: Get all saved matches
    if ($action === 'list' && $method === 'GET') {
        $matches = $repo->getAllInListFormat(100, 0);

        // Sort by timestamp descending (newest first)
        usort($matches, fn($a, $b) => ($b['ts'] ?? 0) <=> ($a['ts'] ?? 0));

        Common::jsonResponse(true, ['items' => $matches]);
    }

    // LOAD: Get specific match
    if ($action === 'load' && $method === 'GET') {
        $id = $_GET['id'] ?? '';

        if (empty($id)) {
            Common::jsonResponse(false, null, 'missing_id');
        }

        $safeId = Common::sanitizeId($id);
        if (empty($safeId)) {
            Common::jsonResponse(false, null, 'invalid_id');
        }

        $matchData = $repo->getInFileFormat($safeId);

        if (!$matchData) {
            Common::jsonResponse(false, null, 'not_found');
        }

        Common::jsonResponse(true, ['payload' => $matchData]);
    }

    // SAVE: Create or update match
    if ($action === 'save' && $method === 'POST') {
        $raw = file_get_contents('php://input');

        if ($raw === false || empty($raw)) {
            Common::jsonResponse(false, null, 'empty_request');
        }

        $in = json_decode($raw, true);

        if (!is_array($in) || !isset($in['payload'])) {
            Common::jsonResponse(false, null, 'bad_payload');
        }

        // Validate CSRF token
        $token = $in['csrf_token'] ?? '';
        if (!Common::validateCsrfToken($token)) {
            Common::jsonResponse(false, null, 'invalid_csrf_token', 403);
        }

        $payload = $in['payload'];

        // Validate payload structure
        if (!validate_payload($payload)) {
            Common::jsonResponse(false, null, 'invalid_structure');
        }

        // Generate or sanitize ID
        if (!empty($in['id'])) {
            $id = Common::sanitizeId($in['id']);
            if (empty($id)) {
                $id = bin2hex(random_bytes(8));
            }
        } else {
            $id = bin2hex(random_bytes(8));
        }

        // Add metadata to payload
        $payload['__saved_at'] = time();
        $payload['__version'] = '2.3';

        // Save to database
        if (!$repo->save($id, $payload)) {
            Common::jsonResponse(false, null, 'save_failed');
        }

        Common::jsonResponse(true, ['id' => $id]);
    }

    // DELETE: Remove match
    if ($action === 'delete' && $method === 'POST') {
        $raw = file_get_contents('php://input');

        if ($raw === false) {
            Common::jsonResponse(false, null, 'empty_request');
        }

        $in = json_decode($raw, true);

        if (!is_array($in)) {
            Common::jsonResponse(false, null, 'bad_request');
        }

        // Validate CSRF token
        $token = $in['csrf_token'] ?? '';
        if (!Common::validateCsrfToken($token)) {
            Common::jsonResponse(false, null, 'invalid_csrf_token', 403);
        }

        $id = $in['id'] ?? '';

        if (empty($id)) {
            Common::jsonResponse(false, null, 'missing_id');
        }

        $safeId = Common::sanitizeId($id);
        if (empty($safeId)) {
            Common::jsonResponse(false, null, 'invalid_id');
        }

        if (!$repo->exists($safeId)) {
            Common::jsonResponse(false, null, 'not_found');
        }

        if ($repo->delete($safeId) === 0) {
            Common::jsonResponse(false, null, 'delete_failed');
        }

        Common::jsonResponse(true);
    }

    // Unknown action
    Common::jsonResponse(false, null, 'bad_action', 400);

} catch (\Throwable $e) {
    error_log('StumpVision API Error: ' . $e->getMessage());
    Common::jsonResponse(false, null, 'server_error', 500);
}
