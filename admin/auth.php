<?php
declare(strict_types=1);

// Use shared session configuration
require_once __DIR__ . '/../api/lib/SessionConfig.php';
require_once __DIR__ . '/config-helper.php';
require_once __DIR__ . '/../api/lib/Common.php';

use StumpVision\Common;

/**
 * StumpVision Admin Authentication
 */

/**
 * Check if user is logged in
 */
function isAdminLoggedIn(): bool
{
    return Common::isAdmin();
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
        session_regenerate_id(true);
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
 * Generate CSRF token for admin panel
 * Uses the shared Common library with admin-specific key
 */
function getAdminCsrfToken(): string
{
    return Common::getCsrfToken('admin_csrf_token');
}

/**
 * Validate CSRF token for admin panel
 * Uses the shared Common library with admin-specific key
 */
function validateAdminCsrfToken(string $token): bool
{
    return Common::validateCsrfToken($token, 'admin_csrf_token');
}
