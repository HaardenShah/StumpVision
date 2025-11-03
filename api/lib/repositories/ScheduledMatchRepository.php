<?php
declare(strict_types=1);

/**
 * StumpVision — api/lib/repositories/ScheduledMatchRepository.php
 * Repository for scheduled match data operations
 */

namespace StumpVision\Repositories;

use StumpVision\Database;

final class ScheduledMatchRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find scheduled match by ID
     *
     * @param string $id Scheduled match ID
     * @return array|null Scheduled match data or null if not found
     */
    public function findById(string $id): ?array
    {
        $sql = "SELECT * FROM scheduled_matches WHERE id = :id";
        $match = $this->db->fetchOne($sql, ['id' => $id]);

        if (!$match) {
            return null;
        }

        // Decode JSON fields
        return $this->decodeScheduledMatchData($match);
    }

    /**
     * Get all scheduled matches
     *
     * @param string|null $status Filter by status (scheduled, in_progress, completed, cancelled)
     * @param int $limit Maximum number of matches
     * @param int $offset Offset for pagination
     * @return array Array of scheduled matches
     */
    public function findAll(?string $status = null, int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT * FROM scheduled_matches WHERE 1=1";
        $params = ['limit' => $limit, 'offset' => $offset];

        if ($status !== null) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY scheduled_date DESC, scheduled_time DESC LIMIT :limit OFFSET :offset";

        $matches = $this->db->fetchAll($sql, $params);

        // Decode JSON fields for each match
        return array_map([$this, 'decodeScheduledMatchData'], $matches);
    }

    /**
     * Get upcoming scheduled matches
     *
     * @param int $limit Maximum number of matches
     * @return array Array of upcoming matches
     */
    public function getUpcoming(int $limit = 10): array
    {
        $today = date('Y-m-d');

        $sql = "SELECT * FROM scheduled_matches
                WHERE status = 'scheduled'
                AND scheduled_date >= :today
                ORDER BY scheduled_date ASC, scheduled_time ASC
                LIMIT :limit";

        $matches = $this->db->fetchAll($sql, ['today' => $today, 'limit' => $limit]);

        return array_map([$this, 'decodeScheduledMatchData'], $matches);
    }

    /**
     * Create a new scheduled match
     *
     * @param string $id Scheduled match ID
     * @param array $matchData Match data
     * @param string $createdBy Username who created
     * @return bool True on success
     */
    public function create(string $id, array $matchData, string $createdBy = 'admin'): bool
    {
        $data = [
            'id' => $id,
            'scheduled_date' => $matchData['scheduled_date'] ?? date('Y-m-d'),
            'scheduled_time' => $matchData['scheduled_time'] ?? '00:00',
            'match_name' => $matchData['match_name'] ?? 'Untitled Match',
            'players' => json_encode($matchData['players'] ?? []),
            'team_a' => json_encode($matchData['teamA'] ?? []),
            'team_b' => json_encode($matchData['teamB'] ?? []),
            'match_format' => $matchData['matchFormat'] ?? 'limited',
            'overs_per_innings' => $matchData['oversPerInnings'] ?? null,
            'wickets_limit' => $matchData['wicketsLimit'] ?? null,
            'toss_winner' => $matchData['tossWinner'] ?? null,
            'toss_decision' => $matchData['tossDecision'] ?? null,
            'opening_bat1' => $matchData['openingBat1'] ?? null,
            'opening_bat2' => $matchData['openingBat2'] ?? null,
            'opening_bowler' => $matchData['openingBowler'] ?? null,
            'match_id' => $matchData['match_id'] ?? null,
            'status' => $matchData['status'] ?? 'scheduled',
            'created_at' => time(),
            'created_by' => $createdBy,
            'updated_at' => time()
        ];

        return $this->db->insert('scheduled_matches', $data);
    }

    /**
     * Update an existing scheduled match
     *
     * @param string $id Scheduled match ID
     * @param array $matchData Updated match data
     * @return int Number of affected rows
     */
    public function update(string $id, array $matchData): int
    {
        $data = ['updated_at' => time()];

        // Update only provided fields
        $fields = [
            'scheduled_date', 'scheduled_time', 'match_name', 'match_format',
            'overs_per_innings', 'wickets_limit', 'toss_winner', 'toss_decision',
            'opening_bat1', 'opening_bat2', 'opening_bowler', 'match_id', 'status'
        ];

        foreach ($fields as $field) {
            $camelCase = lcfirst(str_replace('_', '', ucwords($field, '_')));

            if (isset($matchData[$camelCase])) {
                $data[$field] = $matchData[$camelCase];
            } elseif (isset($matchData[$field])) {
                $data[$field] = $matchData[$field];
            }
        }

        // Handle JSON fields
        if (isset($matchData['players'])) {
            $data['players'] = json_encode($matchData['players']);
        }
        if (isset($matchData['teamA'])) {
            $data['team_a'] = json_encode($matchData['teamA']);
        }
        if (isset($matchData['teamB'])) {
            $data['team_b'] = json_encode($matchData['teamB']);
        }

        return $this->db->update('scheduled_matches', $data, 'id = :id', ['id' => $id]);
    }

    /**
     * Link scheduled match to actual match
     *
     * @param string $id Scheduled match ID
     * @param string $matchId Actual match ID
     * @return int Number of affected rows
     */
    public function linkToMatch(string $id, string $matchId): int
    {
        $data = [
            'match_id' => $matchId,
            'status' => 'in_progress',
            'updated_at' => time()
        ];

        return $this->db->update('scheduled_matches', $data, 'id = :id', ['id' => $id]);
    }

    /**
     * Mark scheduled match as completed
     *
     * @param string $id Scheduled match ID
     * @return int Number of affected rows
     */
    public function markAsCompleted(string $id): int
    {
        $data = [
            'status' => 'completed',
            'updated_at' => time()
        ];

        return $this->db->update('scheduled_matches', $data, 'id = :id', ['id' => $id]);
    }

    /**
     * Cancel a scheduled match
     *
     * @param string $id Scheduled match ID
     * @return int Number of affected rows
     */
    public function cancel(string $id): int
    {
        $data = [
            'status' => 'cancelled',
            'updated_at' => time()
        ];

        return $this->db->update('scheduled_matches', $data, 'id = :id', ['id' => $id]);
    }

    /**
     * Delete a scheduled match (permanent removal)
     *
     * @param string $id Scheduled match ID
     * @return int Number of affected rows
     */
    public function delete(string $id): int
    {
        return $this->db->delete('scheduled_matches', 'id = :id', ['id' => $id]);
    }

    /**
     * Check if scheduled match exists
     *
     * @param string $id Scheduled match ID
     * @return bool True if scheduled match exists
     */
    public function exists(string $id): bool
    {
        $sql = "SELECT COUNT(*) FROM scheduled_matches WHERE id = :id";
        $count = $this->db->fetchColumn($sql, ['id' => $id]);
        return $count > 0;
    }

    /**
     * Get total count of scheduled matches
     *
     * @param string|null $status Filter by status
     * @return int Total number of scheduled matches
     */
    public function count(?string $status = null): int
    {
        $sql = "SELECT COUNT(*) FROM scheduled_matches";
        $params = [];

        if ($status !== null) {
            $sql .= " WHERE status = :status";
            $params['status'] = $status;
        }

        return (int) $this->db->fetchColumn($sql, $params);
    }

    /**
     * Get all scheduled matches as associative array (ID => data)
     * This matches the format of the old scheduled-matches.json file
     *
     * @return array Associative array of scheduled matches
     */
    public function getAllAsAssociativeArray(): array
    {
        $matches = $this->findAll();
        $result = [];

        foreach ($matches as $match) {
            $result[$match['id']] = $match;
        }

        return $result;
    }

    /**
     * Decode JSON fields in scheduled match data
     *
     * @param array $match Scheduled match data from database
     * @return array Scheduled match data with decoded JSON fields
     */
    private function decodeScheduledMatchData(array $match): array
    {
        // Decode JSON fields
        $match['players'] = json_decode($match['players'], true) ?? [];
        $match['team_a'] = json_decode($match['team_a'], true) ?? [];
        $match['team_b'] = json_decode($match['team_b'], true) ?? [];

        // Also provide camelCase versions for API compatibility
        $match['teamA'] = $match['team_a'];
        $match['teamB'] = $match['team_b'];

        return $match;
    }
}
