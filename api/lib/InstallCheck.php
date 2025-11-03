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

        return file_exists($dbPath) && file_exists($configPath);
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
