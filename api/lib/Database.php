<?php
declare(strict_types=1);

/**
 * StumpVision — api/lib/Database.php
 * Database layer for SQLite with PDO
 * Provides connection management, query helpers, and transaction support
 */

namespace StumpVision;

final class Database
{
    private static ?Database $instance = null;
    private \PDO $pdo;
    private string $dbPath;

    /**
     * Private constructor - use getInstance() instead
     */
    private function __construct()
    {
        $this->dbPath = __DIR__ . '/../../data/stumpvision.db';

        // Ensure data directory exists
        $dataDir = dirname($this->dbPath);
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        // Create SQLite connection
        $this->pdo = new \PDO('sqlite:' . $this->dbPath);

        // Set error mode to exceptions
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Return associative arrays by default
        $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        // Enable foreign key support (disabled by default in SQLite)
        $this->pdo->exec('PRAGMA foreign_keys = ON');

        // Enable WAL mode for better concurrent access
        $this->pdo->exec('PRAGMA journal_mode = WAL');
    }

    /**
     * Get singleton instance
     *
     * @return Database
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Get PDO instance for direct access if needed
     *
     * @return \PDO
     */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    /**
     * Get database file path
     *
     * @return string
     */
    public function getDbPath(): string
    {
        return $this->dbPath;
    }

    // ==========================================
    // Transaction Methods
    // ==========================================

    /**
     * Begin a transaction
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit a transaction
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback a transaction
     *
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Check if currently in a transaction
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    // ==========================================
    // Query Methods
    // ==========================================

    /**
     * Execute a query with parameters
     *
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return \PDOStatement
     * @throws \PDOException
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row
     *
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return array|null Returns row as associative array or null if not found
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Fetch all rows
     *
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return array Array of rows (each row is an associative array)
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch single column value
     *
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return mixed The value of the first column
     */
    public function fetchColumn(string $sql, array $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }

    // ==========================================
    // CRUD Helper Methods
    // ==========================================

    /**
     * Insert a row into a table
     *
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return bool True on success
     * @throws \PDOException
     */
    public function insert(string $table, array $data): bool
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('Data array cannot be empty');
        }

        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $placeholders = implode(', ', array_map(fn($k) => ":$k", $keys));

        $sql = "INSERT INTO $table ($fields) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($data);
    }

    /**
     * Update rows in a table
     *
     * @param string $table Table name
     * @param array $data Associative array of column => value to update
     * @param string $where WHERE clause (e.g., "id = :id")
     * @param array $whereParams Parameters for WHERE clause
     * @return int Number of affected rows
     * @throws \PDOException
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('Data array cannot be empty');
        }

        $sets = [];
        foreach (array_keys($data) as $key) {
            $sets[] = "$key = :$key";
        }
        $setClause = implode(', ', $sets);

        $sql = "UPDATE $table SET $setClause WHERE $where";
        $stmt = $this->pdo->prepare($sql);

        // Merge data and where params
        $params = array_merge($data, $whereParams);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Delete rows from a table
     *
     * @param string $table Table name
     * @param string $where WHERE clause (e.g., "id = :id")
     * @param array $params Parameters for WHERE clause
     * @return int Number of affected rows
     * @throws \PDOException
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Get the last inserted row ID
     *
     * @return string
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    // ==========================================
    // Utility Methods
    // ==========================================

    /**
     * Check if a table exists
     *
     * @param string $tableName Table name to check
     * @return bool True if table exists
     */
    public function tableExists(string $tableName): bool
    {
        $sql = "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name=:name";
        $count = $this->fetchColumn($sql, ['name' => $tableName]);
        return $count > 0;
    }

    /**
     * Get list of all tables
     *
     * @return array Array of table names
     */
    public function getTables(): array
    {
        $sql = "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name";
        $tables = $this->fetchAll($sql);
        return array_column($tables, 'name');
    }

    /**
     * Execute raw SQL (for schema migrations, etc.)
     * Use with caution - no parameter binding
     *
     * @param string $sql Raw SQL to execute
     * @return int Number of affected rows
     * @throws \PDOException
     */
    public function exec(string $sql): int
    {
        return $this->pdo->exec($sql);
    }

    /**
     * Get database file size in bytes
     *
     * @return int Size in bytes
     */
    public function getDatabaseSize(): int
    {
        if (file_exists($this->dbPath)) {
            return filesize($this->dbPath);
        }
        return 0;
    }

    /**
     * Vacuum the database (reclaim space and optimize)
     *
     * @return bool True on success
     */
    public function vacuum(): bool
    {
        try {
            $this->pdo->exec('VACUUM');
            return true;
        } catch (\PDOException $e) {
            error_log("Database vacuum failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get database statistics
     *
     * @return array Statistics about the database
     */
    public function getStats(): array
    {
        $stats = [
            'file_size' => $this->getDatabaseSize(),
            'file_size_mb' => round($this->getDatabaseSize() / 1024 / 1024, 2),
            'tables' => $this->getTables(),
            'table_count' => count($this->getTables())
        ];

        // Get row counts for each table
        foreach ($stats['tables'] as $table) {
            try {
                $count = $this->fetchColumn("SELECT COUNT(*) FROM $table");
                $stats['row_counts'][$table] = $count;
            } catch (\PDOException $e) {
                $stats['row_counts'][$table] = 'error';
            }
        }

        return $stats;
    }

    /**
     * Prevent cloning of the singleton
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserialization of the singleton
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}
