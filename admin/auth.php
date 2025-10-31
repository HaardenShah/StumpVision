<?php
declare(strict_types=1);
session_start();

/**
 * StumpVision Admin Authentication
 */

// Configuration - CHANGE THESE!
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', password_hash('changeme', PASSWORD_BCRYPT)); // Change this password!

/**
 * Check if user is logged in
 */
function isAdminLoggedIn(): bool
{
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Require admin login or redirect to login page
 */
function requireAdmin(): void
{
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Login admin user
 */
function loginAdmin(string $username, string $password): bool
{
    if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_login_time'] = time();
        return true;
    }
    return false;
}

/**
 * Logout admin user
 */
function logoutAdmin(): void
{
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_login_time']);
    session_destroy();
}

/**
 * Generate CSRF token
 */
function getAdminCsrfToken(): string
{
    if (!isset($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateAdminCsrfToken(string $token): bool
{
    return isset($_SESSION['admin_csrf_token']) && hash_equals($_SESSION['admin_csrf_token'], $token);
}
