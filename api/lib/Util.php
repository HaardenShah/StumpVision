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