<?php
declare(strict_types=1);

namespace StumpVision;

/**
 * Installation Check Helper
 * Checks if StumpVision is installed and redirects to installer if needed
 */
class InstallCheck
{
    /**
     * Check if StumpVision is installed
     */
    public static function isInstalled(): bool
    {
        $dbPath = __DIR__ . '/../../data/stumpvision.db';
        $configPath = __DIR__ . '/../../config/config.json';

        // Both files must exist
        if (!file_exists($dbPath) || !file_exists($configPath)) {
            return false;
        }

        // Verify config is valid JSON with required fields
        try {
            $configContent = @file_get_contents($configPath);
            if ($configContent === false) {
                return false;
            }

            $config = json_decode($configContent, true);
            if (!is_array($config)) {
                return false;
            }

            // Config must have admin credentials and installed flag
            if (!isset($config['admin_username']) || !isset($config['admin_password_hash']) || empty($config['installed'])) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }

        // Verify database has required tables (not just an empty file)
        try {
            $pdo = new \PDO('sqlite:' . $dbPath);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Critical tables that must exist for a valid installation
            $requiredTables = ['migrations', 'players', 'matches', 'scheduled_matches', 'live_sessions'];

            foreach ($requiredTables as $table) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name=?");
                $stmt->execute([$table]);
                $exists = $stmt->fetchColumn() > 0;

                if (!$exists) {
                    return false;
                }
            }

            // Check if we have at least one migration record
            $stmt = $pdo->query("SELECT COUNT(*) FROM migrations");
            $hasMigrations = $stmt->fetchColumn() > 0;

            return $hasMigrations;

        } catch (\PDOException $e) {
            // If we can't connect or query the database, it's not properly installed
            return false;
        }
    }

    /**
     * Redirect to installer if not installed
     * Call this at the top of entry point files
     */
    public static function requireInstalled(): void
    {
        if (!self::isInstalled()) {
            // Get the base path
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $script = $_SERVER['SCRIPT_NAME'] ?? '';
            $baseDir = dirname($script);
            $baseUrl = $protocol . '://' . $host . $baseDir;

            // Redirect to installer
            header('Location: ' . $baseUrl . '/install.php');
            exit;
        }
    }

    /**
     * Redirect away from installer if already installed
     * Call this at the top of install.php
     */
    public static function requireNotInstalled(): void
    {
        if (self::isInstalled()) {
            // Get the base path
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $script = $_SERVER['SCRIPT_NAME'] ?? '';
            $baseDir = dirname($script);
            $baseUrl = $protocol . '://' . $host . $baseDir;

            // Redirect to setup (match setup)
            header('Location: ' . $baseUrl . '/setup.php');
            exit;
        }
    }
}
