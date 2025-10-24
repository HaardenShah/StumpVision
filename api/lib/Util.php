<?php
declare(strict_types=1);

/**
 * StumpVision — api/lib/Util.php
 * Safe utility helpers for the render pipeline
 */

namespace StumpVision;

final class Util
{
    /**
     * Safe string cast with default fallback
     */
    public static function safeStr($value, string $default = ''): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (string)$value;
        }
        return $default;
    }

    /**
     * Safe integer cast with default fallback
     */
    public static function safeInt($value, int $default = 0): int
    {
        if (is_numeric($value)) {
            return (int)$value;
        }
        return $default;
    }

    /**
     * Find executable in PATH (Unix/Linux)
     * @param string $command Command name to find
     * @return string|null Full path to executable or null if not found
     */
    public static function which(string $command): ?string
    {
        // Security: sanitize command name
        $command = preg_replace('/[^a-zA-Z0-9_-]/', '', $command);
        if (empty($command)) {
            return null;
        }

        // Try using 'which' command (Unix/Linux)
        $output = @shell_exec("which " . escapeshellarg($command) . " 2>/dev/null");
        if ($output && trim($output)) {
            $path = trim($output);
            if (is_executable($path)) {
                return $path;
            }
        }

        // Fallback: check common paths
        $commonPaths = [
            '/usr/bin/',
            '/usr/local/bin/',
            '/opt/homebrew/bin/',
            '/bin/'
        ];

        foreach ($commonPaths as $dir) {
            $fullPath = $dir . $command;
            if (is_executable($fullPath)) {
                return $fullPath;
            }
        }

        return null;
    }

    /**
     * Get project directories
     * Returns: [rootDir, dataDir, cardsDir]
     */
    public static function dirs(): array
    {
        // Go up from api/lib/ to project root
        $apiLibDir = __DIR__;
        $apiDir = dirname($apiLibDir);
        $rootDir = dirname($apiDir);
        
        $dataDir = $rootDir . DIRECTORY_SEPARATOR . 'data';
        $cardsDir = $dataDir . DIRECTORY_SEPARATOR . 'cards';
        
        // Create directories if they don't exist
        if (!is_dir($dataDir)) {
            @mkdir($dataDir, 0755, true);
        }
        if (!is_dir($cardsDir)) {
            @mkdir($cardsDir, 0755, true);
        }
        
        return [$rootDir, $dataDir, $cardsDir];
    }
}