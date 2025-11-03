<?php
declare(strict_types=1);

/**
 * StumpVision — Shared Session Configuration
 * Ensures consistent session handling across admin pages and API endpoints
 */

// Prevent multiple session starts
if (session_status() === PHP_SESSION_ACTIVE) {
    return;
}

// Configure session settings for security and reliability
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Lax');

// Ensure sessions directory exists and use it
$sessionPath = sys_get_temp_dir() . '/stumpvision_sessions';
if (!is_dir($sessionPath)) {
    if (!mkdir($sessionPath, 0700, true) && !is_dir($sessionPath)) {
        error_log("Failed to create session directory: $sessionPath");
    }
}
if (is_dir($sessionPath) && is_writable($sessionPath)) {
    session_save_path($sessionPath);
}

// Start the session
session_start();
