#!/usr/bin/env php
<?php
/**
 * Check system requirements for database migration
 */

echo "===========================================\n";
echo "StumpVision Migration Requirements Check\n";
echo "===========================================\n\n";

$requirements = [
    'PHP Version' => [
        'check' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'message' => 'PHP 7.4+ required (current: ' . PHP_VERSION . ')'
    ],
    'PDO Extension' => [
        'check' => extension_loaded('pdo'),
        'message' => 'PDO extension is required'
    ],
    'PDO SQLite Driver' => [
        'check' => extension_loaded('pdo_sqlite'),
        'message' => 'PDO SQLite driver is required'
    ],
    'JSON Extension' => [
        'check' => extension_loaded('json'),
        'message' => 'JSON extension is required'
    ],
    'Data Directory' => [
        'check' => is_dir(__DIR__ . '/../data'),
        'message' => '/data/ directory exists'
    ],
    'Data Directory Writable' => [
        'check' => is_writable(__DIR__ . '/../data'),
        'message' => '/data/ directory is writable'
    ],
    'Database.php Exists' => [
        'check' => file_exists(__DIR__ . '/../api/lib/Database.php'),
        'message' => 'Database.php file exists'
    ],
    'Common.php Exists' => [
        'check' => file_exists(__DIR__ . '/../api/lib/Common.php'),
        'message' => 'Common.php file exists'
    ]
];

$allPassed = true;

foreach ($requirements as $name => $req) {
    $status = $req['check'] ? '✓' : '✗';
    $statusText = $req['check'] ? 'PASS' : 'FAIL';

    printf("%-30s %s %s\n", $name . ':', $status, $req['message']);

    if (!$req['check']) {
        $allPassed = false;
    }
}

echo "\n";

if ($allPassed) {
    echo "✓ All requirements met! You can proceed with migration.\n";
    echo "\nNext steps:\n";
    echo "  1. Backup your data: cp -r data/ backups/\n";
    echo "  2. Run schema migration: php migrations/migrate.php\n";
    echo "  3. Run data import: php migrations/import_from_files.php\n";
    exit(0);
} else {
    echo "✗ Some requirements are not met.\n";
    echo "\nTo install missing extensions:\n";
    echo "\nDebian/Ubuntu:\n";
    echo "  sudo apt-get install php-sqlite3 php-pdo\n";
    echo "\nCentOS/RHEL:\n";
    echo "  sudo yum install php-pdo php-sqlite3\n";
    echo "\nmacOS (Homebrew):\n";
    echo "  brew install php\n";
    echo "  # SQLite support is usually built-in\n";
    echo "\nAfter installing, restart your web server:\n";
    echo "  sudo systemctl restart apache2   # or nginx/php-fpm\n";
    exit(1);
}
