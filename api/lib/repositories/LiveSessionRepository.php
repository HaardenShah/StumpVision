<?php
declare(strict_types=1);

/**
 * StumpVision — api/lib/repositories/LiveSessionRepository.php
 * Repository for live session data operations
 */

namespace StumpVision\Repositories;

use StumpVision\Database;

final class LiveSessionRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find live session by ID
     *
     * @param string $liveId Live session ID
     * @return array|null Live session data or null if not found
     */
    public function findById(string $liveId): ?array
    {
        $sql = "SELECT * FROM live_sessions WHERE live_id = :live_id";
        $session = $this->db->fetchOne($sql, ['live_id' => $liveId]);

        if (!$session) {
            return null;
        }

        // Decode JSON fields
        return $this->decodeLiveSessionData($session);
    }

    /**
     * Find live session by match ID
     *
     * @param string $matchId Match ID
     * @return array|null Live session data or null if not found
     */
    public function findByMatchId(string $matchId): ?array
    {
        $sql = "SELECT * FROM live_sessions
                WHERE match_id = :match_id
                AND active = 1
                ORDER BY created_at DESC
                LIMIT 1";

        $session = $this->db->fetchOne($sql, ['match_id' => $matchId]);

        if (!$session) {
            return null;
        }

        return $this->decodeLiveSessionData($session);
    }

    /**
     * Get all live sessions
     *
     * @param bool $activeOnly Only return active sessions
     * @param int $limit Maximum number of sessions
     * @param int $offset Offset for pagination
     * @return array Array of live sessions
     */
    public function findAll(bool $activeOnly = false, int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT * FROM live_sessions";
        $params = ['limit' => $limit, 'offset' => $offset];

        if ($activeOnly) {
            $sql .= " WHERE active = 1";
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $sessions = $this->db->fetchAll($sql, $params);

        // Decode JSON fields for each session
        return array_map([$this, 'decodeLiveSessionData'], $sessions);
    }

    /**
     * Get active live sessions
     *
     * @param int $limit Maximum number of sessions
     * @return array Array of active live sessions
     */
    public function getActive(int $limit = 50): array
    {
        return $this->findAll(true, $limit, 0);
    }

    /**
     * Create a new live session
     *
     * @param string $liveId Live session ID
     * @param string $matchId Match ID
     * @param string $ownerSession Owner PHP session ID
     * @param array $currentState Current match state
     * @param string|null $scheduledMatchId Scheduled match ID (optional)
     * @return bool True on success
     */
    public function create(
        string $liveId,
        string $matchId,
        string $ownerSession,
        array $currentState,
        ?string $scheduledMatchId = null
    ): bool {
        $data = [
            'live_id' => $liveId,
            'match_id' => $matchId,
            'scheduled_match_id' => $scheduledMatchId,
            'created_at' => time(),
            'owner_session' => $ownerSession,
            'active' => 1,
            'current_state' => json_encode($currentState),
            'last_updated' => time()
        ];

        return $this->db->insert('live_sessions', $data);
    }

    /**
     * Update live session state
     *
     * @param string $liveId Live session ID
     * @param array $currentState Updated match state
     * @return int Number of affected rows
     */
    public function updateState(string $liveId, array $currentState): int
    {
        $data = [
            'current_state' => json_encode($currentState),
            'last_updated' => time()
        ];

        return $this->db->update('live_sessions', $data, 'live_id = :live_id', ['live_id' => $liveId]);
    }

    /**
     * Stop a live session
     *
     * @param string $liveId Live session ID
     * @param string|null $stoppedBy Username who stopped (optional)
     * @return int Number of affected rows
     */
    public function stop(string $liveId, ?string $stoppedBy = null): int
    {
        $data = [
            'active' => 0,
            'stopped_at' => time(),
            'stopped_by' => $stoppedBy
        ];

        return $this->db->update('live_sessions', $data, 'live_id = :live_id', ['live_id' => $liveId]);
    }

    /**
     * Reactivate a stopped live session
     *
     * @param string $liveId Live session ID
     * @return int Number of affected rows
     */
    public function reactivate(string $liveId): int
    {
        $data = [
            'active' => 1,
            'stopped_at' => null,
            'stopped_by' => null
        ];

        return $this->db->update('live_sessions', $data, 'live_id = :live_id', ['live_id' => $liveId]);
    }

    /**
     * Delete a live session (permanent removal)
     *
     * @param string $liveId Live session ID
     * @return int Number of affected rows
     */
    public function delete(string $liveId): int
    {
        return $this->db->delete('live_sessions', 'live_id = :live_id', ['live_id' => $liveId]);
    }

    /**
     * Check if live session exists
     *
     * @param string $liveId Live session ID
     * @return bool True if live session exists
     */
    public function exists(string $liveId): bool
    {
        $sql = "SELECT COUNT(*) FROM live_sessions WHERE live_id = :live_id";
        $count = $this->db->fetchColumn($sql, ['live_id' => $liveId]);
        return $count > 0;
    }

    /**
     * Check if live session is owned by the current session
     *
     * @param string $liveId Live session ID
     * @param string $sessionId Current PHP session ID
     * @return bool True if owned by the current session
     */
    public function isOwnedBySession(string $liveId, string $sessionId): bool
    {
        $sql = "SELECT COUNT(*) FROM live_sessions
                WHERE live_id = :live_id
                AND owner_session = :session_id";

        $count = $this->db->fetchColumn($sql, [
            'live_id' => $liveId,
            'session_id' => $sessionId
        ]);

        return $count > 0;
    }

    /**
     * Check if match has an active live session
     *
     * @param string $matchId Match ID
     * @return bool True if match has an active live session
     */
    public function matchHasActiveSession(string $matchId): bool
    {
        $sql = "SELECT COUNT(*) FROM live_sessions
                WHERE match_id = :match_id
                AND active = 1";

        $count = $this->db->fetchColumn($sql, ['match_id' => $matchId]);
        return $count > 0;
    }

    /**
     * Get total count of live sessions
     *
     * @param bool $activeOnly Only count active sessions
     * @return int Total number of live sessions
     */
    public function count(bool $activeOnly = false): int
    {
        $sql = "SELECT COUNT(*) FROM live_sessions";

        if ($activeOnly) {
            $sql .= " WHERE active = 1";
        }

        return (int) $this->db->fetchColumn($sql);
    }

    /**
     * Clean up old inactive sessions
     * Deletes sessions that have been inactive for more than specified hours
     *
     * @param int $hoursInactive Number of hours of inactivity before deletion
     * @return int Number of sessions deleted
     */
    public function cleanupOldSessions(int $hoursInactive = 24): int
    {
        $cutoffTime = time() - ($hoursInactive * 3600);

        $sql = "DELETE FROM live_sessions
                WHERE active = 0
                AND stopped_at < :cutoff_time";

        $this->db->query($sql, ['cutoff_time' => $cutoffTime]);

        // PDO doesn't return rowCount for DELETE in SQLite consistently
        // So we'll just return 0 for now
        return 0;
    }

    /**
     * Get sessions with their match details (for admin dashboard)
     *
     * @param bool $activeOnly Only return active sessions
     * @param int $limit Maximum number of sessions
     * @return array Array of live sessions with match details
     */
    public function getWithMatchDetails(bool $activeOnly = false, int $limit = 50): array
    {
        $sql = "SELECT
                    ls.live_id,
                    ls.match_id,
                    ls.created_at,
                    ls.active,
                    ls.last_updated,
                    ls.stopped_at,
                    m.title as match_title
                FROM live_sessions ls
                LEFT JOIN matches m ON ls.match_id = m.id";

        $params = ['limit' => $limit];

        if ($activeOnly) {
            $sql .= " WHERE ls.active = 1";
        }

        $sql .= " ORDER BY ls.created_at DESC LIMIT :limit";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Decode JSON fields in live session data
     *
     * @param array $session Live session data from database
     * @return array Live session data with decoded JSON fields
     */
    private function decodeLiveSessionData(array $session): array
    {
        // Decode JSON field
        $session['current_state'] = json_decode($session['current_state'], true) ?? [];

        // Convert active to boolean
        $session['active'] = (bool) $session['active'];

        return $session;
    }
}
