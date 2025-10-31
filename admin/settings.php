<?php
declare(strict_types=1);
require_once 'auth.php';
require_once 'config-helper.php';
requireAdmin();

$message = '';
$messageType = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!validateAdminCsrfToken($_POST['csrf_token'])) {
        $message = 'Invalid CSRF token';
        $messageType = 'error';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validate current password
        $credentials = Config::getAdminCredentials();
        if (!password_verify($currentPassword, $credentials['password_hash'])) {
            $message = 'Current password is incorrect';
            $messageType = 'error';
        } elseif (strlen($newPassword) < 8) {
            $message = 'New password must be at least 8 characters';
            $messageType = 'error';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'New passwords do not match';
            $messageType = 'error';
        } elseif ($newPassword === 'changeme') {
            $message = 'Please choose a different password than the default';
            $messageType = 'error';
        } else {
            if (Config::updateAdminPassword($newPassword)) {
                clearPasswordChangeRequirement();
                $message = 'Password changed successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to update password';
                $messageType = 'error';
            }
        }
    }
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && !isset($_POST['action'])) {
    if (!validateAdminCsrfToken($_POST['csrf_token'])) {
        $message = 'Invalid CSRF token';
        $messageType = 'error';
    } else {
        $updates = [
            'live_score_enabled' => isset($_POST['live_score_enabled']),
            'allow_public_player_registration' => isset($_POST['allow_public_player_registration']),
            'require_admin_verification' => isset($_POST['require_admin_verification']),
            'enable_debug_mode' => isset($_POST['enable_debug_mode']),
            'app_name' => trim($_POST['app_name'] ?? 'StumpVision'),
            'max_matches_per_day' => (int)($_POST['max_matches_per_day'] ?? 100),
            'auto_cleanup_days' => (int)($_POST['auto_cleanup_days'] ?? 365)
        ];

        if (Config::update($updates)) {
            $message = 'Settings saved successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to save settings';
            $messageType = 'error';
        }
    }
}

// Load current config
$config = Config::getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App Settings - StumpVision Admin</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .settings-section {
            background: var(--card);
            border: 2px solid var(--line);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .settings-section h2 {
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--line);
        }
        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid var(--line);
        }
        .setting-item:last-child {
            border-bottom: none;
        }
        .setting-info {
            flex: 1;
        }
        .setting-info h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .setting-info p {
            font-size: 13px;
            color: var(--muted);
            margin: 0;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--line);
            transition: 0.3s;
            border-radius: 34px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }
        input:checked + .toggle-slider {
            background-color: var(--success);
        }
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
        .setting-control input[type="text"],
        .setting-control input[type="number"] {
            width: 200px;
            padding: 8px 12px;
            border: 2px solid var(--line);
            border-radius: 8px;
            background: var(--bg);
            color: var(--ink);
        }
        .save-button-container {
            position: sticky;
            bottom: 20px;
            background: var(--bg);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 -4px 12px var(--shadow);
            text-align: center;
        }
        .btn-save {
            padding: 14px 32px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn-save:hover {
            transform: translateY(-2px);
        }
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 2px solid;
        }
        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border-color: #22c55e;
            color: #22c55e;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border-color: #ef4444;
            color: #ef4444;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <h1>App Settings</h1>

        <p style="margin-bottom: 20px; color: var(--muted);">
            Configure your StumpVision installation. Changes take effect immediately.
        </p>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (mustChangePassword()): ?>
            <div class="alert alert-error" style="margin-bottom: 20px;">
                ⚠️ <strong>Security Warning:</strong> You are using the default password. Please change it immediately!
            </div>
        <?php endif; ?>

        <!-- Password Change Form -->
        <div class="settings-section">
            <h2>🔐 Change Admin Password</h2>

            <form method="POST" style="margin: 0;">
                <input type="hidden" name="csrf_token" value="<?php echo getAdminCsrfToken(); ?>">
                <input type="hidden" name="action" value="change_password">

                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Current Password</h3>
                        <p>Enter your current password to verify</p>
                    </div>
                    <div class="setting-control">
                        <input type="password" name="current_password" required autocomplete="current-password">
                    </div>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <h3>New Password</h3>
                        <p>Must be at least 8 characters</p>
                    </div>
                    <div class="setting-control">
                        <input type="password" name="new_password" required minlength="8" autocomplete="new-password">
                    </div>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Confirm New Password</h3>
                        <p>Re-enter your new password</p>
                    </div>
                    <div class="setting-control">
                        <input type="password" name="confirm_password" required minlength="8" autocomplete="new-password">
                    </div>
                </div>

                <div style="padding: 16px 0; text-align: right;">
                    <button type="submit" class="btn-save" style="display: inline-block;">
                        🔐 Change Password
                    </button>
                </div>
            </form>
        </div>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo getAdminCsrfToken(); ?>">

            <!-- Live Score Sharing -->
            <div class="settings-section">
                <h2>🔴 Live Score Sharing</h2>

                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Enable Live Score Sharing</h3>
                        <p>Allow users to generate shareable links for real-time score viewing</p>
                    </div>
                    <div class="setting-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="live_score_enabled" <?php echo $config['live_score_enabled'] ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Player Management -->
            <div class="settings-section">
                <h2>👤 Player Management</h2>

                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Allow Public Player Registration</h3>
                        <p>Let users register players themselves (not recommended for public sites)</p>
                    </div>
                    <div class="setting-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="allow_public_player_registration" <?php echo $config['allow_public_player_registration'] ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Require Admin Verification</h3>
                        <p>Only verified matches count toward player statistics</p>
                    </div>
                    <div class="setting-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="require_admin_verification" <?php echo $config['require_admin_verification'] ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- General Settings -->
            <div class="settings-section">
                <h2>⚙️ General Settings</h2>

                <div class="setting-item">
                    <div class="setting-info">
                        <h3>App Name</h3>
                        <p>Display name for your installation</p>
                    </div>
                    <div class="setting-control">
                        <input type="text" name="app_name" value="<?php echo htmlspecialchars($config['app_name']); ?>" required>
                    </div>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Max Matches Per Day</h3>
                        <p>Rate limit for match creation (per IP)</p>
                    </div>
                    <div class="setting-control">
                        <input type="number" name="max_matches_per_day" value="<?php echo $config['max_matches_per_day']; ?>" min="1" max="1000" required>
                    </div>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Auto-Cleanup After (Days)</h3>
                        <p>Automatically delete unverified matches older than this (0 = disabled)</p>
                    </div>
                    <div class="setting-control">
                        <input type="number" name="auto_cleanup_days" value="<?php echo $config['auto_cleanup_days']; ?>" min="0" max="3650" required>
                    </div>
                </div>
            </div>

            <!-- Developer Settings -->
            <div class="settings-section">
                <h2>🛠️ Developer Settings</h2>

                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Debug Mode</h3>
                        <p>Show detailed error messages (disable in production)</p>
                    </div>
                    <div class="setting-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="enable_debug_mode" <?php echo $config['enable_debug_mode'] ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="save-button-container">
                <button type="submit" class="btn-save">💾 Save All Settings</button>
            </div>
        </form>

        <div class="card" style="margin-top: 24px;">
            <h2>ℹ️ About These Settings</h2>
            <ul style="list-style: none; padding: 0;">
                <li style="padding: 8px 0;">
                    <strong>Live Score Sharing:</strong> When enabled, users can generate shareable links. Viewers see real-time score updates.
                </li>
                <li style="padding: 8px 0;">
                    <strong>Public Player Registration:</strong> If enabled, anyone can register players via API. Recommended to keep disabled for public sites.
                </li>
                <li style="padding: 8px 0;">
                    <strong>Admin Verification:</strong> When enabled, only verified matches count toward aggregate player statistics.
                </li>
                <li style="padding: 8px 0;">
                    <strong>Auto-Cleanup:</strong> Set to 0 to disable automatic deletion of old unverified matches.
                </li>
            </ul>
        </div>
    </div>
</body>
</html>
