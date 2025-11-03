<?php
declare(strict_types=1);

/**
 * StumpVision — api/install.php
 * Initial setup/installation API
 */

// Don't start session for install
header('Content-Type: application/json');

// Check if already installed
$dbPath = __DIR__ . '/../data/stumpvision.db';
$configPath = __DIR__ . '/../config/config.json';

if (file_exists($dbPath) && file_exists($configPath)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'err' => 'already_installed',
        'message' => 'StumpVision is already installed. Delete the database and config files to reinstall.'
    ]);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'err' => 'method_not_allowed']);
    exit;
}

// Get request body
$raw = file_get_contents('php://input');
if (empty($raw)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'err' => 'empty_request']);
    exit;
}

$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'err' => 'invalid_json']);
    exit;
}

// Validate required fields
$siteName = $data['site_name'] ?? '';
$adminUsername = $data['admin_username'] ?? '';
$adminPassword = $data['admin_password'] ?? '';

if (empty($siteName) || empty($adminUsername) || empty($adminPassword)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'err' => 'missing_fields']);
    exit;
}

// Validate username format
if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $adminUsername)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'err' => 'invalid_username']);
    exit;
}

// Validate password length
if (strlen($adminPassword) < 8) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'err' => 'password_too_short']);
    exit;
}

try {
    // Step 1: Create database and run migrations
    createDatabaseAndMigrate();

    // Step 2: Create config file with admin credentials
    createConfig($siteName, $adminUsername, $adminPassword);

    // Success!
    echo json_encode([
        'ok' => true,
        'message' => 'Installation completed successfully'
    ]);

} catch (Exception $e) {
    // Cleanup on error
    if (file_exists($dbPath)) {
        @unlink($dbPath);
    }
    if (file_exists($configPath)) {
        @unlink($configPath);
    }

    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'err' => 'installation_failed',
        'message' => $e->getMessage()
    ]);
}

function createDatabaseAndMigrate(): void
{
    $dbPath = __DIR__ . '/../data/stumpvision.db';
    $dataDir = dirname($dbPath);

    // Create data directory if it doesn't exist
    if (!is_dir($dataDir)) {
        if (!mkdir($dataDir, 0755, true)) {
            throw new Exception('Failed to create data directory');
        }
    }

    // Create database connection
    try {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        throw new Exception('Failed to create database: ' . $e->getMessage());
    }

    // Read and execute migration
    $schemaFile = __DIR__ . '/../migrations/001_initial_schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception('Schema file not found');
    }

    $schema = file_get_contents($schemaFile);
    if ($schema === false) {
        throw new Exception('Failed to read schema file');
    }

    // Execute schema
    try {
        $pdo->exec($schema);
    } catch (PDOException $e) {
        throw new Exception('Failed to run migrations: ' . $e->getMessage());
    }
}

function createConfig(string $siteName, string $adminUsername, string $adminPassword): void
{
    $configPath = __DIR__ . '/../config/config.json';
    $configDir = dirname($configPath);

    // Create config directory if it doesn't exist
    if (!is_dir($configDir)) {
        if (!mkdir($configDir, 0750, true)) {
            throw new Exception('Failed to create config directory');
        }
    }

    // Hash password
    $passwordHash = password_hash($adminPassword, PASSWORD_BCRYPT);

    // Create config
    $config = [
        'app_name' => $siteName,
        'version' => '2.2',
        'admin_username' => $adminUsername,
        'admin_password_hash' => $passwordHash,
        'live_score_enabled' => false,
        'allow_public_player_registration' => false,
        'max_matches_per_day' => 100,
        'require_admin_verification' => true,
        'auto_cleanup_days' => 365,
        'enable_debug_mode' => false,
        'installed' => true,
        'installed_at' => date('Y-m-d H:i:s')
    ];

    // Write config file
    $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new Exception('Failed to encode config');
    }

    if (file_put_contents($configPath, $json) === false) {
        throw new Exception('Failed to write config file');
    }

    // Set restrictive permissions (only owner can read/write)
    chmod($configPath, 0600);
}
