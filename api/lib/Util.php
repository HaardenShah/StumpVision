<?php
declare(strict_types=1);

/**
 * StumpVision — api/lib/Util.php
 * Small, safe utility helpers for the render pipeline.
 */

namespace StumpVision;

final class Util
{
    /** Safe string cast with default. */
    public static function safeStr($v, string $def = ''): string
    {
        return is_string($v) ? $v : (is_numeric($v) ? (string)$v : $def);
    }

    /** Safe int cast with default. */
    public static function safeInt($v, int $def = 0): int
    {
        return is_numeric($v) ? (int)$v : $def;
    }

    /** Find a binary on PATH (returns full path or null). */
    public static function which(string $bin): ?string
    {
        $path = getenv('PATH') ?: '';
        foreach (explode(PATH_SEPARATOR, $path) as $p) {
            $full = rtrim($p, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $bin;
            if (is_file($full) && is_executable($full)) {
                return $full;
            }
        }
        return null;
    }

    /** Project directories. */
    public static function dirs(): array
    {
        $root    = dirname(__DIR__, 1); // api/
        $root    = dirname($root);      // project root
        $dataDir = $root . DIRECTORY_SEPARATOR . 'data';
        $cards   = $dataDir . DIRECTORY_SEPARATOR . 'cards';
        if (!is_dir($cards)) @mkdir($cards, 0775, true);
        return [$root, $dataDir, $cards];
    }
}
