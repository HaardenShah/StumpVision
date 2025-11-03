# Database Migration Strategy for StumpVision

## Executive Summary

This document outlines the strategy for migrating StumpVision from a file-based JSON storage system to a relational database. The migration maintains API compatibility, improves performance, enables better querying, and prepares the application for scaling.

---

## 1. Current Architecture Analysis

### File-Based Storage Overview

**Storage Locations:**
- `/data/*.json` - Individual match files (one per match)
- `/data/players.json` - Single player registry file
- `/data/scheduled-matches.json` - Scheduled matches registry
- `/data/live/*.json` - Live session files
- `/config/config.json` - Admin configuration
- `/tmp/stumpvision_sessions/` - PHP session files

**Current Data Volume:**
- Match files: ~5-50 KB each
- Players registry: ~10-100 KB
- Live sessions: ~1-5 KB each
- Config: <1 KB

**File Operations:**
- All use `Common.php` safe file operations with locking
- Full read-modify-write pattern for updates
- File locking prevents race conditions
- No streaming or partial updates

---

## 2. Proposed Database Architecture

### 2.1 Database Choice

**Recommended: SQLite** (Phase 1)
- ✅ No additional server setup required
- ✅ Single file database - easy backup/migration
- ✅ Built into PHP 7.4+
- ✅ ACID compliant with transactions
- ✅ Perfect for single-server deployments
- ✅ JSON support for flexible fields
- ⚠️ Limited to ~1TB storage (sufficient for most use cases)
- ⚠️ Single writer at a time (similar to current file locking)

**Future: MySQL/PostgreSQL** (Phase 2 - Optional)
- For high-traffic deployments
- Multiple concurrent writers
- Replication support
- Better for distributed systems

### 2.2 Database Schema

```sql
-- Players table
CREATE TABLE players (
    id TEXT PRIMARY KEY,  -- UUID v4 format
    name TEXT NOT NULL,
    code TEXT UNIQUE NOT NULL,  -- Player code (e.g., VIKO-1234)
    team TEXT,
    player_type TEXT CHECK(player_type IN ('Batsman', 'Bowler', 'All-rounder')),
    registered_at INTEGER NOT NULL,  -- Unix timestamp
    registered_by TEXT NOT NULL,
    updated_at INTEGER,
    deleted_at INTEGER DEFAULT NULL,  -- Soft delete
    INDEX idx_code (code),
    INDEX idx_name (name),
    INDEX idx_team (team)
);

-- Matches table
CREATE TABLE matches (
    id TEXT PRIMARY KEY,  -- 16-char hex ID
    created_at INTEGER NOT NULL,  -- Unix timestamp
    updated_at INTEGER NOT NULL,
    deleted_at INTEGER DEFAULT NULL,  -- Soft delete

    -- Match metadata
    title TEXT NOT NULL,
    overs_per_side INTEGER NOT NULL,
    wickets_limit INTEGER NOT NULL,

    -- Match data (stored as JSON for flexibility)
    teams TEXT NOT NULL,  -- JSON: [{"name": "...", "players": [...]}, ...]
    innings TEXT NOT NULL,  -- JSON: [{"batStats": [...], "bowlStats": [...]}, ...]

    -- Verification
    verified INTEGER DEFAULT 0,  -- Boolean: 0 or 1
    verified_at INTEGER,
    verified_by TEXT,

    -- Version tracking
    version TEXT DEFAULT '2.3',

    INDEX idx_created_at (created_at),
    INDEX idx_verified (verified),
    INDEX idx_title (title)
);

-- Scheduled matches table
CREATE TABLE scheduled_matches (
    id TEXT PRIMARY KEY,  -- 6-digit numeric ID
    scheduled_date TEXT NOT NULL,  -- YYYY-MM-DD
    scheduled_time TEXT NOT NULL,  -- HH:MM
    match_name TEXT NOT NULL,

    -- Match setup (stored as JSON)
    players TEXT,  -- JSON: array of player IDs
    team_a TEXT NOT NULL,  -- JSON: {"name": "...", "players": [...]}
    team_b TEXT NOT NULL,  -- JSON: {"name": "...", "players": [...]}

    -- Match settings
    match_format TEXT NOT NULL,  -- 'limited' or 'unlimited'
    overs_per_innings INTEGER,
    wickets_limit INTEGER,

    -- Toss details
    toss_winner TEXT,
    toss_decision TEXT,
    opening_bat1 TEXT,
    opening_bat2 TEXT,
    opening_bowler TEXT,

    -- Link to actual match
    match_id TEXT,  -- Foreign key to matches.id (when match starts)

    -- Status
    status TEXT DEFAULT 'scheduled' CHECK(status IN ('scheduled', 'in_progress', 'completed', 'cancelled')),

    -- Audit fields
    created_at INTEGER NOT NULL,
    created_by TEXT NOT NULL,
    updated_at INTEGER,

    INDEX idx_scheduled_date (scheduled_date),
    INDEX idx_status (status),
    INDEX idx_match_id (match_id),
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE SET NULL
);

-- Live sessions table
CREATE TABLE live_sessions (
    live_id TEXT PRIMARY KEY,  -- 16-char hex ID
    match_id TEXT NOT NULL,  -- Foreign key to matches.id
    scheduled_match_id TEXT,  -- Foreign key to scheduled_matches.id (nullable)

    created_at INTEGER NOT NULL,
    owner_session TEXT NOT NULL,  -- PHP session ID

    active INTEGER DEFAULT 1,  -- Boolean: 0 or 1
    current_state TEXT NOT NULL,  -- JSON: complete match state snapshot

    last_updated INTEGER NOT NULL,
    stopped_at INTEGER,
    stopped_by TEXT,

    INDEX idx_match_id (match_id),
    INDEX idx_active (active),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (scheduled_match_id) REFERENCES scheduled_matches(id) ON DELETE SET NULL
);

-- Configuration table (optional - config.json can remain as file)
CREATE TABLE config (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL,  -- JSON encoded value
    updated_at INTEGER NOT NULL,
    updated_by TEXT
);

-- Migration tracking
CREATE TABLE migrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    version TEXT NOT NULL UNIQUE,
    applied_at INTEGER NOT NULL,
    description TEXT
);
```

### 2.3 Why Use JSON Columns?

**Advantages:**
- ✅ Maintains flexibility of current schema
- ✅ No breaking changes to API responses
- ✅ Easy to add new fields without migrations
- ✅ Direct mapping from current JSON files
- ✅ SQLite has excellent JSON support (json_extract, json_each, etc.)

**When to normalize:**
- Consider normalizing `innings.batStats` and `innings.bowlStats` to separate tables ONLY if:
  - Need complex queries on individual player performances
  - Building advanced statistics/leaderboards
  - This can be Phase 3 optimization

---

## 3. Migration Implementation Plan

### Phase 1: Foundation (Week 1)

**Step 1.1: Create Database Layer**
- Create `/api/lib/Database.php` - PDO wrapper with:
  - Connection management (singleton pattern)
  - Query helpers (select, insert, update, delete)
  - Transaction support
  - Prepared statement helpers
  - Error handling and logging

**Step 1.2: Create Schema Migration**
- Create `/migrations/001_initial_schema.sql`
- Create `/migrations/migrate.php` - Migration runner script

**Step 1.3: Create Data Migration Script**
- Create `/migrations/import_from_files.php`:
  - Read all JSON files
  - Convert to database records
  - Maintain ID consistency
  - Validate data integrity
  - Generate migration report

### Phase 2: API Layer Updates (Week 2)

**Step 2.1: Create Repository Pattern**
Create repository classes for each entity:
- `/api/lib/repositories/MatchRepository.php`
- `/api/lib/repositories/PlayerRepository.php`
- `/api/lib/repositories/ScheduledMatchRepository.php`
- `/api/lib/repositories/LiveSessionRepository.php`

**Step 2.2: Update API Endpoints**
Update in this order (least to most complex):
1. `api/players.php` - Simplest, single table
2. `api/scheduled-matches.php` - Simple, single table
3. `api/live.php` - Medium complexity
4. `api/matches.php` - Most complex

**Step 2.3: Update Admin Panel**
Update admin pages to use new repositories:
- `admin/index.php` - Dashboard stats
- `admin/players.php` - Player management
- `admin/matches.php` - Match management
- `admin/live-sessions.php` - Live session management
- `admin/stats.php` - Statistics aggregation

### Phase 3: Testing & Deployment (Week 3)

**Step 3.1: Testing**
- Unit tests for repositories
- Integration tests for API endpoints
- Performance testing vs file-based system
- Data integrity validation
- Concurrent access testing

**Step 3.2: Dual-Mode Operation (Optional)**
- Add feature flag to switch between file/database
- Useful for gradual rollout
- Allows fallback if issues discovered

**Step 3.3: Deployment**
1. Backup all existing JSON files
2. Run migration script
3. Verify data integrity
4. Deploy updated code
5. Monitor for errors
6. Keep JSON files as backup for 30 days

---

## 4. Detailed Component Design

### 4.1 Database.php - Core Database Layer

```php
<?php
namespace StumpVision;

class Database
{
    private static ?Database $instance = null;
    private \PDO $pdo;

    private function __construct() {
        $dbPath = __DIR__ . '/../../data/stumpvision.db';
        $this->pdo = new \PDO('sqlite:' . $dbPath);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getPdo(): \PDO {
        return $this->pdo;
    }

    // Transaction helpers
    public function beginTransaction(): bool {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool {
        return $this->pdo->commit();
    }

    public function rollback(): bool {
        return $this->pdo->rollBack();
    }

    // Query helpers with prepared statements
    public function query(string $sql, array $params = []): \PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchOne(string $sql, array $params = []): ?array {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function insert(string $table, array $data): bool {
        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $placeholders = implode(', ', array_map(fn($k) => ":$k", $keys));

        $sql = "INSERT INTO $table ($fields) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($data);
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): bool {
        $sets = [];
        foreach ($data as $key => $value) {
            $sets[] = "$key = :$key";
        }
        $setClause = implode(', ', $sets);

        $sql = "UPDATE $table SET $setClause WHERE $where";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute(array_merge($data, $whereParams));
    }

    public function delete(string $table, string $where, array $params = []): bool {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function lastInsertId(): string {
        return $this->pdo->lastInsertId();
    }
}
```

### 4.2 MatchRepository.php - Example Repository

```php
<?php
namespace StumpVision;

class MatchRepository
{
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById(string $id): ?array {
        $sql = "SELECT * FROM matches WHERE id = :id AND deleted_at IS NULL";
        $match = $this->db->fetchOne($sql, ['id' => $id]);

        if (!$match) {
            return null;
        }

        // Decode JSON fields
        $match['teams'] = json_decode($match['teams'], true);
        $match['innings'] = json_decode($match['innings'], true);
        $match['verified'] = (bool) $match['verified'];

        return $match;
    }

    public function findAll(int $limit = 100, int $offset = 0, bool $verifiedOnly = false): array {
        $sql = "SELECT * FROM matches WHERE deleted_at IS NULL";

        if ($verifiedOnly) {
            $sql .= " AND verified = 1";
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $matches = $this->db->fetchAll($sql, ['limit' => $limit, 'offset' => $offset]);

        // Decode JSON fields for each match
        foreach ($matches as &$match) {
            $match['teams'] = json_decode($match['teams'], true);
            $match['innings'] = json_decode($match['innings'], true);
            $match['verified'] = (bool) $match['verified'];
        }

        return $matches;
    }

    public function create(array $matchData): bool {
        $data = [
            'id' => $matchData['id'],
            'created_at' => time(),
            'updated_at' => time(),
            'title' => $matchData['meta']['title'] ?? 'Untitled Match',
            'overs_per_side' => $matchData['meta']['oversPerSide'] ?? 20,
            'wickets_limit' => $matchData['meta']['wicketsLimit'] ?? 10,
            'teams' => json_encode($matchData['teams'] ?? []),
            'innings' => json_encode($matchData['innings'] ?? []),
            'verified' => 0,
            'version' => '2.3'
        ];

        return $this->db->insert('matches', $data);
    }

    public function update(string $id, array $matchData): bool {
        $data = [
            'updated_at' => time(),
            'title' => $matchData['meta']['title'] ?? 'Untitled Match',
            'overs_per_side' => $matchData['meta']['oversPerSide'] ?? 20,
            'wickets_limit' => $matchData['meta']['wicketsLimit'] ?? 10,
            'teams' => json_encode($matchData['teams'] ?? []),
            'innings' => json_encode($matchData['innings'] ?? [])
        ];

        return $this->db->update('matches', $data, 'id = :id', ['id' => $id]);
    }

    public function verify(string $id, string $verifiedBy): bool {
        $data = [
            'verified' => 1,
            'verified_at' => time(),
            'verified_by' => $verifiedBy,
            'updated_at' => time()
        ];

        return $this->db->update('matches', $data, 'id = :id', ['id' => $id]);
    }

    public function delete(string $id): bool {
        // Soft delete
        $data = ['deleted_at' => time()];
        return $this->db->update('matches', $data, 'id = :id', ['id' => $id]);
    }

    public function count(bool $verifiedOnly = false): int {
        $sql = "SELECT COUNT(*) as count FROM matches WHERE deleted_at IS NULL";

        if ($verifiedOnly) {
            $sql .= " AND verified = 1";
        }

        $result = $this->db->fetchOne($sql);
        return (int) ($result['count'] ?? 0);
    }
}
```

### 4.3 Migration Script

```php
<?php
// migrations/import_from_files.php
require_once __DIR__ . '/../api/lib/Common.php';
require_once __DIR__ . '/../api/lib/Database.php';

use StumpVision\Common;
use StumpVision\Database;

$db = Database::getInstance();
$report = [
    'players' => ['success' => 0, 'failed' => 0],
    'matches' => ['success' => 0, 'failed' => 0],
    'scheduled_matches' => ['success' => 0, 'failed' => 0],
    'live_sessions' => ['success' => 0, 'failed' => 0]
];

// Start transaction
$db->beginTransaction();

try {
    // 1. Migrate players
    $playersFile = __DIR__ . '/../data/players.json';
    if (file_exists($playersFile)) {
        $result = Common::safeJsonRead($playersFile);
        if ($result['ok']) {
            $players = $result['data'];
            foreach ($players as $player) {
                try {
                    $db->insert('players', [
                        'id' => $player['id'],
                        'name' => $player['name'],
                        'code' => $player['code'],
                        'team' => $player['team'] ?? '',
                        'player_type' => $player['player_type'] ?? 'Batsman',
                        'registered_at' => $player['registered_at'],
                        'registered_by' => $player['registered_by'] ?? 'admin',
                        'updated_at' => $player['updated_at'] ?? time()
                    ]);
                    $report['players']['success']++;
                } catch (Exception $e) {
                    $report['players']['failed']++;
                    error_log("Failed to migrate player {$player['id']}: " . $e->getMessage());
                }
            }
        }
    }

    // 2. Migrate matches
    $matchFiles = glob(__DIR__ . '/../data/*.json');
    foreach ($matchFiles as $file) {
        if (basename($file) === 'players.json' || basename($file) === 'scheduled-matches.json') {
            continue;
        }

        $result = Common::safeJsonRead($file);
        if ($result['ok']) {
            $match = $result['data'];
            $matchId = basename($file, '.json');

            try {
                $db->insert('matches', [
                    'id' => $matchId,
                    'created_at' => $match['__saved_at'] ?? filemtime($file),
                    'updated_at' => $match['__saved_at'] ?? filemtime($file),
                    'title' => $match['meta']['title'] ?? 'Untitled Match',
                    'overs_per_side' => $match['meta']['oversPerSide'] ?? 20,
                    'wickets_limit' => $match['meta']['wicketsLimit'] ?? 10,
                    'teams' => json_encode($match['teams'] ?? []),
                    'innings' => json_encode($match['innings'] ?? []),
                    'verified' => isset($match['__verified']) && $match['__verified'] ? 1 : 0,
                    'verified_at' => $match['__verified_at'] ?? null,
                    'verified_by' => $match['__verified_by'] ?? null,
                    'version' => $match['__version'] ?? '2.3'
                ]);
                $report['matches']['success']++;
            } catch (Exception $e) {
                $report['matches']['failed']++;
                error_log("Failed to migrate match {$matchId}: " . $e->getMessage());
            }
        }
    }

    // 3. Migrate scheduled matches
    $scheduledFile = __DIR__ . '/../data/scheduled-matches.json';
    if (file_exists($scheduledFile)) {
        $result = Common::safeJsonRead($scheduledFile);
        if ($result['ok']) {
            $scheduled = $result['data'];
            foreach ($scheduled as $match) {
                try {
                    $db->insert('scheduled_matches', [
                        'id' => $match['id'],
                        'scheduled_date' => $match['scheduled_date'],
                        'scheduled_time' => $match['scheduled_time'],
                        'match_name' => $match['match_name'],
                        'players' => json_encode($match['players'] ?? []),
                        'team_a' => json_encode($match['teamA'] ?? []),
                        'team_b' => json_encode($match['teamB'] ?? []),
                        'match_format' => $match['matchFormat'] ?? 'limited',
                        'overs_per_innings' => $match['oversPerInnings'] ?? null,
                        'wickets_limit' => $match['wicketsLimit'] ?? null,
                        'toss_winner' => $match['tossWinner'] ?? null,
                        'toss_decision' => $match['tossDecision'] ?? null,
                        'opening_bat1' => $match['openingBat1'] ?? null,
                        'opening_bat2' => $match['openingBat2'] ?? null,
                        'opening_bowler' => $match['openingBowler'] ?? null,
                        'match_id' => $match['match_id'] ?? null,
                        'status' => $match['status'] ?? 'scheduled',
                        'created_at' => $match['created_at'] ?? time(),
                        'created_by' => $match['created_by'] ?? 'admin',
                        'updated_at' => $match['updated_at'] ?? time()
                    ]);
                    $report['scheduled_matches']['success']++;
                } catch (Exception $e) {
                    $report['scheduled_matches']['failed']++;
                    error_log("Failed to migrate scheduled match {$match['id']}: " . $e->getMessage());
                }
            }
        }
    }

    // 4. Migrate live sessions
    $liveFiles = glob(__DIR__ . '/../data/live/*.json');
    foreach ($liveFiles as $file) {
        $result = Common::safeJsonRead($file);
        if ($result['ok']) {
            $session = $result['data'];
            $liveId = basename($file, '.json');

            try {
                $db->insert('live_sessions', [
                    'live_id' => $liveId,
                    'match_id' => $session['match_id'],
                    'scheduled_match_id' => $session['scheduled_match_id'] ?? null,
                    'created_at' => $session['created_at'],
                    'owner_session' => $session['owner_session'],
                    'active' => $session['active'] ? 1 : 0,
                    'current_state' => json_encode($session['current_state'] ?? []),
                    'last_updated' => $session['last_updated'],
                    'stopped_at' => $session['stopped_at'] ?? null,
                    'stopped_by' => $session['stopped_by'] ?? null
                ]);
                $report['live_sessions']['success']++;
            } catch (Exception $e) {
                $report['live_sessions']['failed']++;
                error_log("Failed to migrate live session {$liveId}: " . $e->getMessage());
            }
        }
    }

    // Commit transaction
    $db->commit();

    // Print report
    echo "Migration Complete!\n";
    echo "==================\n\n";
    echo "Players: {$report['players']['success']} succeeded, {$report['players']['failed']} failed\n";
    echo "Matches: {$report['matches']['success']} succeeded, {$report['matches']['failed']} failed\n";
    echo "Scheduled Matches: {$report['scheduled_matches']['success']} succeeded, {$report['scheduled_matches']['failed']} failed\n";
    echo "Live Sessions: {$report['live_sessions']['success']} succeeded, {$report['live_sessions']['failed']} failed\n";

} catch (Exception $e) {
    $db->rollback();
    echo "Migration FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
```

---

## 5. Benefits of Migration

### Performance Improvements
1. **Faster queries** - Indexed searches vs full file scans
2. **Better filtering** - SQL WHERE clauses vs loading all data
3. **Pagination** - True LIMIT/OFFSET vs loading everything
4. **Statistics** - Aggregate queries (COUNT, SUM, AVG) vs manual iteration
5. **Concurrent access** - Database handles locking automatically

### Scalability Benefits
1. **No file system limits** - Handle millions of matches
2. **Better for multiple servers** - Shared database vs file sync
3. **Backup/restore** - Single database file vs thousands of JSON files
4. **Replication** - Easy database replication for high availability

### Development Benefits
1. **Complex queries** - JOIN tables for relationships
2. **Data integrity** - Foreign keys enforce relationships
3. **Transactions** - Atomic operations across multiple tables
4. **Standards** - SQL is universal, file formats are custom
5. **Tools** - Database GUIs, query analyzers, monitoring

---

## 6. Migration Risks & Mitigation

### Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Data loss during migration | HIGH | Full backup before migration, rollback plan |
| Performance regression | MEDIUM | Benchmark before/after, optimize queries |
| Bugs in new code | MEDIUM | Thorough testing, phased rollout |
| Compatibility issues | LOW | Maintain API contract, version checks |
| User disruption | MEDIUM | Schedule during low-traffic period |

### Rollback Plan
1. Keep all JSON files for 30 days post-migration
2. Create `/api/lib/FileStorage.php` - wrapper for old file operations
3. Add feature flag to switch between database/files
4. Monitor error logs for 7 days post-deployment
5. If critical issues: disable database, re-enable file storage

---

## 7. Testing Strategy

### Unit Tests
- Test each repository method independently
- Mock database connections
- Validate data transformations
- Test error handling

### Integration Tests
- Test API endpoints with database
- Validate CSRF protection still works
- Test rate limiting with database
- Verify admin panel functionality

### Performance Tests
- Benchmark query times vs file operations
- Test with realistic data volumes (1000+ matches)
- Concurrent access testing
- Memory usage profiling

### Data Integrity Tests
- Compare checksums of migrated data
- Validate all relationships
- Check for missing records
- Verify JSON decode/encode correctness

---

## 8. Deployment Checklist

### Pre-Deployment
- [ ] Full backup of `/data/` directory
- [ ] Test migration script on copy of data
- [ ] Run all unit and integration tests
- [ ] Performance benchmark current system
- [ ] Review all code changes
- [ ] Document rollback procedure

### Deployment
- [ ] Put site in maintenance mode
- [ ] Create database file (`/data/stumpvision.db`)
- [ ] Run schema migration (`001_initial_schema.sql`)
- [ ] Run data migration (`import_from_files.php`)
- [ ] Verify migration report (check for failures)
- [ ] Deploy updated code
- [ ] Test all API endpoints manually
- [ ] Test admin panel functions
- [ ] Remove maintenance mode

### Post-Deployment
- [ ] Monitor error logs for 24 hours
- [ ] Check database file size
- [ ] Run performance benchmarks
- [ ] Validate user reports
- [ ] Keep JSON files for 30 days
- [ ] Document any issues discovered
- [ ] Update README with database info

---

## 9. Alternative Approaches Considered

### Option 1: Hybrid Approach (Not Recommended)
- Keep files for some data, database for others
- **Pros:** Gradual migration, lower risk
- **Cons:** Complex, two systems to maintain, no unified queries

### Option 2: NoSQL Database (Not Recommended)
- Use MongoDB, CouchDB, etc.
- **Pros:** Direct JSON storage, flexible schema
- **Cons:** Requires additional server, overkill for this use case

### Option 3: Stay with Files (Current System)
- Improve file-based system with indexing
- **Pros:** No migration needed, works well currently
- **Cons:** Limited scalability, complex queries difficult

**Verdict:** SQLite migration (Recommended) offers best balance of:
- Simplicity (single file, no server)
- Performance (indexed queries)
- Features (SQL, transactions, relationships)
- Compatibility (minimal PHP changes)

---

## 10. Timeline Estimate

| Phase | Tasks | Duration | Dependencies |
|-------|-------|----------|--------------|
| **Phase 1: Foundation** | Database layer, schema, migration script | 5 days | None |
| **Phase 2: API Updates** | Repositories, endpoint updates, admin panel | 5 days | Phase 1 |
| **Phase 3: Testing** | Unit tests, integration tests, performance tests | 3 days | Phase 2 |
| **Phase 4: Deployment** | Backup, migrate, deploy, monitor | 2 days | Phase 3 |
| **Total** | | **15 days** (3 weeks) | |

---

## 11. Next Steps

1. **Review and Approval** - Get stakeholder approval for this strategy
2. **Environment Setup** - Set up development environment with test data
3. **Begin Phase 1** - Start implementing database layer
4. **Iterative Development** - Build and test incrementally
5. **Production Migration** - Execute deployment plan

---

## 12. Conclusion

Migrating from file-based storage to SQLite database will:
- ✅ Improve performance for queries and statistics
- ✅ Enable better data relationships and integrity
- ✅ Prepare for future scaling needs
- ✅ Maintain backward compatibility
- ✅ Provide better developer experience

The migration is **low-risk** with proper backups and rollback plan. SQLite is the **optimal choice** for StumpVision's current scale while providing room for growth.

**Recommended:** Proceed with this migration strategy.

---

*Document Version: 1.0*
*Last Updated: 2024-11-03*
*Author: Migration Planning Team*
