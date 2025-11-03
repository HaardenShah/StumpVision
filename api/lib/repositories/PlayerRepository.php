<?php
declare(strict_types=1);

/**
 * StumpVision — api/lib/repositories/PlayerRepository.php
 * Repository for player data operations
 */

namespace StumpVision\Repositories;

use StumpVision\Database;

final class PlayerRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find player by ID
     *
     * @param string $id Player UUID
     * @return array|null Player data or null if not found
     */
    public function findById(string $id): ?array
    {
        $sql = "SELECT * FROM players WHERE id = :id AND deleted_at IS NULL";
        return $this->db->fetchOne($sql, ['id' => $id]);
    }

    /**
     * Find player by code
     *
     * @param string $code Player code (e.g., VIKO-1234)
     * @return array|null Player data or null if not found
     */
    public function findByCode(string $code): ?array
    {
        $sql = "SELECT * FROM players WHERE code = :code AND deleted_at IS NULL";
        return $this->db->fetchOne($sql, ['code' => $code]);
    }

    /**
     * Get all players
     *
     * @param int $limit Maximum number of players to return
     * @param int $offset Offset for pagination
     * @return array Array of players
     */
    public function findAll(int $limit = 1000, int $offset = 0): array
    {
        $sql = "SELECT * FROM players
                WHERE deleted_at IS NULL
                ORDER BY name ASC
                LIMIT :limit OFFSET :offset";

        return $this->db->fetchAll($sql, ['limit' => $limit, 'offset' => $offset]);
    }

    /**
     * Search players by name
     *
     * @param string $query Search query
     * @param int $limit Maximum results
     * @return array Array of matching players
     */
    public function searchByName(string $query, int $limit = 50): array
    {
        $sql = "SELECT * FROM players
                WHERE deleted_at IS NULL
                AND name LIKE :query
                ORDER BY name ASC
                LIMIT :limit";

        return $this->db->fetchAll($sql, [
            'query' => '%' . $query . '%',
            'limit' => $limit
        ]);
    }

    /**
     * Get players by team
     *
     * @param string $team Team name
     * @return array Array of players in the team
     */
    public function findByTeam(string $team): array
    {
        $sql = "SELECT * FROM players
                WHERE deleted_at IS NULL
                AND team = :team
                ORDER BY name ASC";

        return $this->db->fetchAll($sql, ['team' => $team]);
    }

    /**
     * Create a new player
     *
     * @param array $playerData Player data
     * @return bool True on success
     */
    public function create(array $playerData): bool
    {
        $data = [
            'id' => $playerData['id'],
            'name' => $playerData['name'],
            'code' => $playerData['code'],
            'team' => $playerData['team'] ?? '',
            'player_type' => $playerData['player_type'] ?? 'Batsman',
            'registered_at' => time(),
            'registered_by' => $playerData['registered_by'] ?? 'system',
            'updated_at' => time()
        ];

        return $this->db->insert('players', $data);
    }

    /**
     * Update an existing player
     *
     * @param string $id Player UUID
     * @param array $playerData Updated player data
     * @return int Number of affected rows
     */
    public function update(string $id, array $playerData): int
    {
        $data = [
            'updated_at' => time()
        ];

        // Only update fields that are provided
        if (isset($playerData['name'])) {
            $data['name'] = $playerData['name'];
        }
        if (isset($playerData['code'])) {
            $data['code'] = $playerData['code'];
        }
        if (isset($playerData['team'])) {
            $data['team'] = $playerData['team'];
        }
        if (isset($playerData['player_type'])) {
            $data['player_type'] = $playerData['player_type'];
        }

        return $this->db->update('players', $data, 'id = :id', ['id' => $id]);
    }

    /**
     * Delete a player (soft delete)
     *
     * @param string $id Player UUID
     * @return int Number of affected rows
     */
    public function delete(string $id): int
    {
        $data = ['deleted_at' => time()];
        return $this->db->update('players', $data, 'id = :id', ['id' => $id]);
    }

    /**
     * Hard delete a player (permanent removal)
     *
     * @param string $id Player UUID
     * @return int Number of affected rows
     */
    public function hardDelete(string $id): int
    {
        return $this->db->delete('players', 'id = :id', ['id' => $id]);
    }

    /**
     * Check if player exists
     *
     * @param string $id Player UUID
     * @return bool True if player exists
     */
    public function exists(string $id): bool
    {
        $sql = "SELECT COUNT(*) FROM players WHERE id = :id AND deleted_at IS NULL";
        $count = $this->db->fetchColumn($sql, ['id' => $id]);
        return $count > 0;
    }

    /**
     * Check if player code is unique
     *
     * @param string $code Player code
     * @param string|null $excludeId Player ID to exclude (for updates)
     * @return bool True if code is unique
     */
    public function isCodeUnique(string $code, ?string $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM players WHERE code = :code AND deleted_at IS NULL";
        $params = ['code' => $code];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $count = $this->db->fetchColumn($sql, $params);
        return $count === 0;
    }

    /**
     * Get total count of players
     *
     * @return int Total number of players (excluding deleted)
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM players WHERE deleted_at IS NULL";
        return (int) $this->db->fetchColumn($sql);
    }

    /**
     * Get all players as associative array (ID => player data)
     * This matches the format of the old players.json file
     *
     * @return array Associative array of players
     */
    public function getAllAsAssociativeArray(): array
    {
        $players = $this->findAll();
        $result = [];

        foreach ($players as $player) {
            $result[$player['id']] = $player;
        }

        return $result;
    }

    /**
     * Verify a player (mark as verified)
     * Used when linking player to match data
     *
     * @param string $playerId Player UUID
     * @return bool True if player exists
     */
    public function verify(string $playerId): bool
    {
        return $this->exists($playerId);
    }
}
