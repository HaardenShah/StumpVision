<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config-helper.php';

/**
 * StumpVision Admin Authentication
 */

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
 * Check if password change is required and redirect to settings
 * Call this on pages other than settings.php
 */
function checkPasswordChangeRequired(): void
{
    if (mustChangePassword() && basename($_SERVER['PHP_SELF']) !== 'settings.php') {
        header('Location: settings.php');
        exit;
    }
}

/**
 * Login admin user
 */
function loginAdmin(string $username, string $password): bool
{
    $credentials = Config::getAdminCredentials();

    if ($username === $credentials['username'] && password_verify($password, $credentials['password_hash'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_login_time'] = time();
        $_SESSION['must_change_password'] = Config::isUsingDefaultPassword();
        return true;
    }
    return false;
}

/**
 * Check if admin must change password
 */
function mustChangePassword(): bool
{
    return isset($_SESSION['must_change_password']) && $_SESSION['must_change_password'] === true;
}

/**
 * Clear password change requirement
 */
function clearPasswordChangeRequirement(): void
{
    $_SESSION['must_change_password'] = false;
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
