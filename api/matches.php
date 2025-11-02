<?php
declare(strict_types=1);
session_start();

/**
 * StumpVision — api/matches.php
 * JSON CRUD for match data with enhanced security
 */

require_once __DIR__ . '/lib/Common.php';

use StumpVision\Common;

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    Common::jsonResponse(false, null, 'PHP 7.4+ required', 500);
}

// Send security headers
Common::sendSecurityHeaders('DENY');

$dataDir = __DIR__ . '/../data';

// Ensure data directory exists
if (!Common::ensureDirectory($dataDir)) {
    Common::jsonResponse(false, null, 'Cannot create data directory', 500);
}

// Check write permissions
if (!is_writable($dataDir)) {
    Common::jsonResponse(false, null, 'Data directory not writable', 500);
}

/**
 * Get file path for match ID
 */
function path_for(string $id): string
{
    global $dataDir;
    return $dataDir . DIRECTORY_SEPARATOR . Common::sanitizeId($id) . '.json';
}

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
        $items = [];
        $files = glob($dataDir . DIRECTORY_SEPARATOR . '*.json');

        if ($files === false) {
            Common::jsonResponse(true, ['items' => []]);
        }

        foreach ($files as $f) {
            $id = basename($f, '.json');
            $result = Common::safeJsonRead($f);

            if (!$result['ok'] || !is_array($result['data'])) {
                continue;
            }

            $j = $result['data'];
            $title = $j['meta']['title'] ??
                    (($j['teams'][0]['name'] ?? 'Team A') . ' vs ' . ($j['teams'][1]['name'] ?? 'Team B'));

            $items[] = [
                'id' => $id,
                'ts' => filemtime($f),
                'title' => $title
            ];
        }

        // Sort by timestamp descending (newest first)
        usort($items, fn($a, $b) => $b['ts'] <=> $a['ts']);

        Common::jsonResponse(true, ['items' => $items]);
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

        $f = path_for($safeId);
        $result = Common::safeJsonRead($f);

        if (!$result['ok']) {
            Common::jsonResponse(false, null, $result['error']);
        }

        if (!is_array($result['data'])) {
            Common::jsonResponse(false, null, 'invalid_json');
        }

        Common::jsonResponse(true, ['payload' => $result['data']]);
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

        $f = path_for($id);

        // Add metadata
        $payload['__saved_at'] = time();
        $payload['__version'] = '2.2';

        // Write to file with locking
        if (!Common::safeJsonWrite($f, $payload)) {
            Common::jsonResponse(false, null, 'write_error');
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

        $f = path_for($safeId);

        if (!is_file($f)) {
            Common::jsonResponse(false, null, 'not_found');
        }

        if (!unlink($f)) {
            Common::jsonResponse(false, null, 'delete_error');
        }

        Common::jsonResponse(true);
    }

    // Unknown action
    Common::jsonResponse(false, null, 'bad_action', 400);

} catch (\Throwable $e) {
    error_log('StumpVision API Error: ' . $e->getMessage());
    Common::jsonResponse(false, null, 'server_error', 500);
}
