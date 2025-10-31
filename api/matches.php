<?php
declare(strict_types=1);
session_start();

/**
 * StumpVision — api/matches.php
 * JSON CRUD for match data with enhanced security
 */

// Security headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// CORS (optional - uncomment if needed)
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type');

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => 'PHP 7.4+ required']);
    exit;
}

$dataDir = __DIR__ . '/../data';

// Ensure data directory exists
if (!is_dir($dataDir)) {
    if (!@mkdir($dataDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'err' => 'Cannot create data directory']);
        exit;
    }
}

// Check write permissions
if (!is_writable($dataDir)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => 'Data directory not writable']);
    exit;
}

/**
 * Sanitize match ID - only allow safe characters
 */
function safe_id(string $id): string
{
    // Remove any directory traversal attempts
    $id = basename($id);
    $id = str_replace(['..', '/', '\\'], '', $id);

    // Only allow alphanumeric, underscore, and hyphen
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id);

    // Limit length
    return substr($id, 0, 64);
}

/**
 * Generate CSRF token for session
 */
function get_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validate_csrf_token(string $token): bool
{
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Simple rate limiting - max 60 requests per minute per IP
 */
function check_rate_limit(): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'rate_limit_' . md5($ip);

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'reset' => time() + 60];
    }

    $data = $_SESSION[$key];

    // Reset if window expired
    if (time() >= $data['reset']) {
        $_SESSION[$key] = ['count' => 1, 'reset' => time() + 60];
        return true;
    }

    // Check limit
    if ($data['count'] >= 60) {
        return false;
    }

    // Increment counter
    $_SESSION[$key]['count']++;
    return true;
}

/**
 * Get file path for match ID
 */
function path_for(string $id): string
{
    global $dataDir;
    return $dataDir . DIRECTORY_SEPARATOR . safe_id($id) . '.json';
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

// Check rate limit for all requests
if (!check_rate_limit()) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'err' => 'rate_limit_exceeded']);
    exit;
}

try {
    // GET CSRF TOKEN: Return token for client
    if ($action === 'get-token' && $method === 'GET') {
        echo json_encode(['ok' => true, 'token' => get_csrf_token()]);
        exit;
    }

    // LIST: Get all saved matches
    if ($action === 'list' && $method === 'GET') {
        $items = [];
        $files = glob($dataDir . DIRECTORY_SEPARATOR . '*.json');
        
        if ($files === false) {
            echo json_encode(['ok' => true, 'items' => []]);
            exit;
        }
        
        foreach ($files as $f) {
            $id = basename($f, '.json');
            $content = @file_get_contents($f);
            
            if ($content === false) {
                continue;
            }
            
            $j = json_decode($content, true);
            if (!is_array($j)) {
                continue;
            }
            
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
        
        echo json_encode(['ok' => true, 'items' => $items]);
        exit;
    }

    // LOAD: Get specific match
    if ($action === 'load' && $method === 'GET') {
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            echo json_encode(['ok' => false, 'err' => 'missing_id']);
            exit;
        }
        
        $safeId = safe_id($id);
        if (empty($safeId)) {
            echo json_encode(['ok' => false, 'err' => 'invalid_id']);
            exit;
        }
        
        $f = path_for($safeId);
        
        if (!is_file($f)) {
            echo json_encode(['ok' => false, 'err' => 'not_found']);
            exit;
        }
        
        $content = file_get_contents($f);
        if ($content === false) {
            echo json_encode(['ok' => false, 'err' => 'read_error']);
            exit;
        }
        
        $payload = json_decode($content, true);
        if (!is_array($payload)) {
            echo json_encode(['ok' => false, 'err' => 'invalid_json']);
            exit;
        }
        
        echo json_encode(['ok' => true, 'payload' => $payload]);
        exit;
    }

    // SAVE: Create or update match
    if ($action === 'save' && $method === 'POST') {
        $raw = file_get_contents('php://input');

        if ($raw === false || empty($raw)) {
            echo json_encode(['ok' => false, 'err' => 'empty_request']);
            exit;
        }

        $in = json_decode($raw, true);

        if (!is_array($in) || !isset($in['payload'])) {
            echo json_encode(['ok' => false, 'err' => 'bad_payload']);
            exit;
        }

        // Validate CSRF token
        $token = $in['csrf_token'] ?? '';
        if (!validate_csrf_token($token)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'err' => 'invalid_csrf_token']);
            exit;
        }
        
        $payload = $in['payload'];
        
        // Validate payload structure
        if (!validate_payload($payload)) {
            echo json_encode(['ok' => false, 'err' => 'invalid_structure']);
            exit;
        }
        
        // Generate or sanitize ID
        if (!empty($in['id'])) {
            $id = safe_id($in['id']);
            if (empty($id)) {
                $id = bin2hex(random_bytes(8));
            }
        } else {
            $id = bin2hex(random_bytes(8));
        }
        
        $f = path_for($id);
        
        // Add metadata
        $payload['__saved_at'] = time();
        $payload['__version'] = '2.0';
        
        // Write to file
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            echo json_encode(['ok' => false, 'err' => 'json_encode_error']);
            exit;
        }
        
        if (file_put_contents($f, $json) === false) {
            echo json_encode(['ok' => false, 'err' => 'write_error']);
            exit;
        }
        
        echo json_encode(['ok' => true, 'id' => $id]);
        exit;
    }

    // DELETE: Remove match
    if ($action === 'delete' && $method === 'POST') {
        $raw = file_get_contents('php://input');

        if ($raw === false) {
            echo json_encode(['ok' => false, 'err' => 'empty_request']);
            exit;
        }

        $in = json_decode($raw, true);

        if (!is_array($in)) {
            echo json_encode(['ok' => false, 'err' => 'bad_request']);
            exit;
        }

        // Validate CSRF token
        $token = $in['csrf_token'] ?? '';
        if (!validate_csrf_token($token)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'err' => 'invalid_csrf_token']);
            exit;
        }
        
        $id = $in['id'] ?? '';
        
        if (empty($id)) {
            echo json_encode(['ok' => false, 'err' => 'missing_id']);
            exit;
        }
        
        $safeId = safe_id($id);
        if (empty($safeId)) {
            echo json_encode(['ok' => false, 'err' => 'invalid_id']);
            exit;
        }
        
        $f = path_for($safeId);
        
        if (!is_file($f)) {
            echo json_encode(['ok' => false, 'err' => 'not_found']);
            exit;
        }
        
        if (!unlink($f)) {
            echo json_encode(['ok' => false, 'err' => 'delete_error']);
            exit;
        }
        
        echo json_encode(['ok' => true]);
        exit;
    }

    // Unknown action
    http_response_code(400);
    echo json_encode(['ok' => false, 'err' => 'bad_action']);
    
} catch (\Throwable $e) {
    error_log('StumpVision API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => 'server_error']);
}