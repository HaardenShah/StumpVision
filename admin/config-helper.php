<?php
declare(strict_types=1);

/**
 * Configuration Helper
 * Manages app settings stored in data/config.json
 */

class Config
{
    private static string $configFile = __DIR__ . '/../data/config.json';
    private static ?array $cache = null;

    /**
     * Get all config settings
     */
    public static function getAll(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        if (!is_file(self::$configFile)) {
            self::createDefaultConfig();
        }

        $content = file_get_contents(self::$configFile);
        $config = json_decode($content, true);

        if (!is_array($config)) {
            $config = self::getDefaults();
        }

        self::$cache = $config;
        return $config;
    }

    /**
     * Get specific config value
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $config = self::getAll();
        return $config[$key] ?? $default;
    }

    /**
     * Set config value
     */
    public static function set(string $key, mixed $value): bool
    {
        $config = self::getAll();
        $config[$key] = $value;
        return self::saveConfig($config);
    }

    /**
     * Update multiple config values
     */
    public static function update(array $values): bool
    {
        $config = self::getAll();
        foreach ($values as $key => $value) {
            $config[$key] = $value;
        }
        return self::saveConfig($config);
    }

    /**
     * Get default configuration
     */
    private static function getDefaults(): array
    {
        return [
            'live_score_enabled' => false,
            'app_name' => 'StumpVision',
            'version' => '2.2',
            'allow_public_player_registration' => false,
            'max_matches_per_day' => 100,
            'require_admin_verification' => true,
            'auto_cleanup_days' => 365,
            'enable_debug_mode' => false
        ];
    }

    /**
     * Create default config file
     */
    private static function createDefaultConfig(): void
    {
        $dir = dirname(self::$configFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $defaults = self::getDefaults();
        file_put_contents(
            self::$configFile,
            json_encode($defaults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Save config to file
     */
    private static function saveConfig(array $config): bool
    {
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return false;
        }

        $result = file_put_contents(self::$configFile, $json);
        if ($result !== false) {
            self::$cache = $config; // Update cache
            return true;
        }

        return false;
    }

    /**
     * Check if live score sharing is enabled
     */
    public static function isLiveScoreEnabled(): bool
    {
        return (bool) self::get('live_score_enabled', false);
    }

    /**
     * Enable/disable live score sharing
     */
    public static function setLiveScoreEnabled(bool $enabled): bool
    {
        return self::set('live_score_enabled', $enabled);
    }

    /**
     * Get admin credentials
     */
    public static function getAdminCredentials(): array
    {
        $username = self::get('admin_username', 'admin');
        $passwordHash = self::get('admin_password_hash', null);

        // If no password hash exists, create default
        if ($passwordHash === null) {
            $passwordHash = password_hash('changeme', PASSWORD_BCRYPT);
            self::set('admin_password_hash', $passwordHash);
        }

        return [
            'username' => $username,
            'password_hash' => $passwordHash
        ];
    }

    /**
     * Update admin password
     */
    public static function updateAdminPassword(string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        return self::set('admin_password_hash', $hash);
    }

    /**
     * Update admin username
     */
    public static function updateAdminUsername(string $newUsername): bool
    {
        return self::set('admin_username', $newUsername);
    }

    /**
     * Check if using default password
     */
    public static function isUsingDefaultPassword(): bool
    {
        $credentials = self::getAdminCredentials();
        return password_verify('changeme', $credentials['password_hash']);
    }
}
