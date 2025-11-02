<?php
declare(strict_types=1);

/**
 * StumpVision — api/lib/Common.php
 * Shared utility functions used across the application
 * Consolidates duplicate code and provides consistent security functions
 */

namespace StumpVision;

final class Common
{
    /**
     * Sanitize ID - only allow safe characters
     * Used for match IDs, live IDs, and any file-based identifiers
     *
     * @param string $id The ID to sanitize
     * @return string Sanitized ID (max 64 chars, alphanumeric + underscore + hyphen)
     */
    public static function sanitizeId(string $id): string
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
     *
     * @param string $key Session key to store token (default: 'csrf_token')
     * @return string The CSRF token
     */
    public static function getCsrfToken(string $key = 'csrf_token'): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return '';
        }

        if (empty($_SESSION[$key])) {
            $_SESSION[$key] = bin2hex(random_bytes(32));
        }
        return $_SESSION[$key];
    }

    /**
     * Validate CSRF token
     *
     * @param string $token Token to validate
     * @param string $key Session key where token is stored (default: 'csrf_token')
     * @return bool True if valid, false otherwise
     */
    public static function validateCsrfToken(string $token, string $key = 'csrf_token'): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        if (empty($_SESSION[$key])) {
            return false;
        }
        return hash_equals($_SESSION[$key], $token);
    }

    /**
     * Check rate limiting - configurable requests per minute per IP
     *
     * @param int $maxRequests Maximum requests allowed per minute (default: 60)
     * @param string $key Session key prefix for rate limiting (default: 'rate_limit')
     * @return bool True if within limit, false if exceeded
     */
    public static function checkRateLimit(int $maxRequests = 60, string $key = 'rate_limit'): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return true; // Allow if session not available
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $sessionKey = $key . '_' . md5($ip);

        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = ['count' => 0, 'reset' => time() + 60];
        }

        $data = $_SESSION[$sessionKey];

        // Reset if window expired
        if (time() >= $data['reset']) {
            $_SESSION[$sessionKey] = ['count' => 1, 'reset' => time() + 60];
            return true;
        }

        // Check limit
        if ($data['count'] >= $maxRequests) {
            return false;
        }

        // Increment counter
        $_SESSION[$sessionKey]['count']++;
        return true;
    }

    /**
     * Check if user is admin (logged in to admin panel)
     *
     * @return bool True if admin is logged in
     */
    public static function isAdmin(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }

    /**
     * Safe file read with error handling
     *
     * @param string $path File path to read
     * @return array Returns ['ok' => bool, 'content' => string|null, 'error' => string|null]
     */
    public static function safeFileRead(string $path): array
    {
        if (!is_file($path)) {
            return ['ok' => false, 'content' => null, 'error' => 'file_not_found'];
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return ['ok' => false, 'content' => null, 'error' => 'read_error'];
        }

        return ['ok' => true, 'content' => $content, 'error' => null];
    }

    /**
     * Safe JSON file read with error handling
     *
     * @param string $path File path to read
     * @param bool $associative Return associative array (default: true)
     * @return array Returns ['ok' => bool, 'data' => mixed|null, 'error' => string|null]
     */
    public static function safeJsonRead(string $path, bool $associative = true): array
    {
        $result = self::safeFileRead($path);
        if (!$result['ok']) {
            return ['ok' => false, 'data' => null, 'error' => $result['error']];
        }

        $data = json_decode($result['content'], $associative);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            return ['ok' => false, 'data' => null, 'error' => 'invalid_json'];
        }

        return ['ok' => true, 'data' => $data, 'error' => null];
    }

    /**
     * Safe file write with locking to prevent race conditions
     *
     * @param string $path File path to write
     * @param string $content Content to write
     * @param bool $useLock Use file locking (default: true)
     * @return bool True on success, false on failure
     */
    public static function safeFileWrite(string $path, string $content, bool $useLock = true): bool
    {
        if ($useLock) {
            $fp = fopen($path, 'c');
            if ($fp === false) {
                return false;
            }

            // Acquire exclusive lock
            if (!flock($fp, LOCK_EX)) {
                fclose($fp);
                return false;
            }

            // Truncate file
            ftruncate($fp, 0);
            rewind($fp);

            // Write content
            $result = fwrite($fp, $content);

            // Release lock and close
            flock($fp, LOCK_UN);
            fclose($fp);

            return $result !== false;
        }

        return file_put_contents($path, $content) !== false;
    }

    /**
     * Safe JSON file write with locking
     *
     * @param string $path File path to write
     * @param mixed $data Data to encode as JSON
     * @param bool $useLock Use file locking (default: true)
     * @return bool True on success, false on failure
     */
    public static function safeJsonWrite(string $path, $data, bool $useLock = true): bool
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return false;
        }

        return self::safeFileWrite($path, $json, $useLock);
    }

    /**
     * Ensure directory exists with proper permissions
     *
     * @param string $path Directory path
     * @param int $permissions Permissions (default: 0755)
     * @return bool True if directory exists or was created, false on failure
     */
    public static function ensureDirectory(string $path, int $permissions = 0755): bool
    {
        if (is_dir($path)) {
            return true;
        }

        if (!mkdir($path, $permissions, true) && !is_dir($path)) {
            error_log("Failed to create directory: $path");
            return false;
        }

        return true;
    }

    /**
     * Send standard security headers
     *
     * @param string $frameOptions X-Frame-Options value (default: 'DENY')
     */
    public static function sendSecurityHeaders(string $frameOptions = 'DENY'): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header("X-Frame-Options: $frameOptions");
        header('X-XSS-Protection: 1; mode=block');
    }

    /**
     * Send JSON response and exit
     *
     * @param bool $success Success status
     * @param mixed $data Data to include in response
     * @param string|null $error Error message if any
     * @param int $httpCode HTTP status code (default: 200)
     */
    public static function jsonResponse(bool $success, $data = null, ?string $error = null, int $httpCode = 200): void
    {
        if ($httpCode !== 200) {
            http_response_code($httpCode);
        }

        $response = ['ok' => $success];

        if ($data !== null) {
            if (is_array($data)) {
                $response = array_merge($response, $data);
            } else {
                $response['data'] = $data;
            }
        }

        if ($error !== null) {
            $response['err'] = $error;
        }

        echo json_encode($response);
        exit;
    }
}
