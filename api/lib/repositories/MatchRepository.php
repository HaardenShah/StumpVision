<?php
declare(strict_types=1);

/**
 * StumpVision — api/lib/repositories/MatchRepository.php
 * Repository for match data operations
 */

namespace StumpVision\Repositories;

use StumpVision\Database;

final class MatchRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find match by ID
     *
     * @param string $id Match ID
     * @return array|null Match data or null if not found
     */
    public function findById(string $id): ?array
    {
        $sql = "SELECT * FROM matches WHERE id = :id AND deleted_at IS NULL";
        $match = $this->db->fetchOne($sql, ['id' => $id]);

        if (!$match) {
            return null;
        }

        // Decode JSON fields
        return $this->decodeMatchData($match);
    }

    /**
     * Get all matches
     *
     * @param int $limit Maximum number of matches
     * @param int $offset Offset for pagination
     * @param bool $verifiedOnly Only return verified matches
     * @return array Array of matches
     */
    public function findAll(int $limit = 100, int $offset = 0, bool $verifiedOnly = false): array
    {
        $sql = "SELECT * FROM matches WHERE deleted_at IS NULL";

        if ($verifiedOnly) {
            $sql .= " AND verified = 1";
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $matches = $this->db->fetchAll($sql, ['limit' => $limit, 'offset' => $offset]);

        // Decode JSON fields for each match
        return array_map([$this, 'decodeMatchData'], $matches);
    }

    /**
     * Get matches list with minimal data (for admin dashboard)
     *
     * @param int $limit Maximum number of matches
     * @param int $offset Offset for pagination
     * @return array Array of match summaries
     */
    public function getMatchesList(int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT id, created_at, title, verified, verified_at, verified_by
                FROM matches
                WHERE deleted_at IS NULL
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset";

        return $this->db->fetchAll($sql, ['limit' => $limit, 'offset' => $offset]);
    }

    /**
     * Create a new match
     *
     * @param string $id Match ID
     * @param array $matchData Match data
     * @return bool True on success
     */
    public function create(string $id, array $matchData): bool
    {
        $meta = $matchData['meta'] ?? [];

        $data = [
            'id' => $id,
            'created_at' => $matchData['__saved_at'] ?? time(),
            'updated_at' => time(),
            'title' => $meta['title'] ?? 'Untitled Match',
            'overs_per_side' => $meta['oversPerSide'] ?? 20,
            'wickets_limit' => $meta['wicketsLimit'] ?? 10,
            'teams' => json_encode($matchData['teams'] ?? []),
            'innings' => json_encode($matchData['innings'] ?? []),
            'verified' => 0,
            'version' => $matchData['__version'] ?? '2.3'
        ];

        return $this->db->insert('matches', $data);
    }

    /**
     * Update an existing match
     *
     * @param string $id Match ID
     * @param array $matchData Updated match data
     * @return int Number of affected rows
     */
    public function update(string $id, array $matchData): int
    {
        $meta = $matchData['meta'] ?? [];

        $data = [
            'updated_at' => time(),
            'title' => $meta['title'] ?? 'Untitled Match',
            'overs_per_side' => $meta['oversPerSide'] ?? 20,
            'wickets_limit' => $meta['wicketsLimit'] ?? 10,
            'teams' => json_encode($matchData['teams'] ?? []),
            'innings' => json_encode($matchData['innings'] ?? [])
        ];

        return $this->db->update('matches', $data, 'id = :id', ['id' => $id]);
    }

    /**
     * Save a match (create or update)
     *
     * @param string $id Match ID
     * @param array $matchData Match data
     * @return bool True on success
     */
    public function save(string $id, array $matchData): bool
    {
        if ($this->exists($id)) {
            return $this->update($id, $matchData) > 0;
        } else {
            return $this->create($id, $matchData);
        }
    }

    /**
     * Verify a match
     *
     * @param string $id Match ID
     * @param string $verifiedBy Username who verified
     * @return int Number of affected rows
     */
    public function verify(string $id, string $verifiedBy): int
    {
        $data = [
            'verified' => 1,
            'verified_at' => time(),
            'verified_by' => $verifiedBy,
            'updated_at' => time()
        ];

        return $this->db->update('matches', $data, 'id = :id', ['id' => $id]);
    }

    /**
     * Unverify a match
     *
     * @param string $id Match ID
     * @return int Number of affected rows
     */
    public function unverify(string $id): int
    {
        $data = [
            'verified' => 0,
            'verified_at' => null,
            'verified_by' => null,
            'updated_at' => time()
        ];

        return $this->db->update('matches', $data, 'id = :id', ['id' => $id]);
    }

    /**
     * Delete a match (soft delete)
     *
     * @param string $id Match ID
     * @return int Number of affected rows
     */
    public function delete(string $id): int
    {
        $data = ['deleted_at' => time()];
        return $this->db->update('matches', $data, 'id = :id', ['id' => $id]);
    }

    /**
     * Hard delete a match (permanent removal)
     *
     * @param string $id Match ID
     * @return int Number of affected rows
     */
    public function hardDelete(string $id): int
    {
        return $this->db->delete('matches', 'id = :id', ['id' => $id]);
    }

    /**
     * Check if match exists
     *
     * @param string $id Match ID
     * @return bool True if match exists
     */
    public function exists(string $id): bool
    {
        $sql = "SELECT COUNT(*) FROM matches WHERE id = :id AND deleted_at IS NULL";
        $count = $this->db->fetchColumn($sql, ['id' => $id]);
        return $count > 0;
    }

    /**
     * Get total count of matches
     *
     * @param bool $verifiedOnly Only count verified matches
     * @return int Total number of matches
     */
    public function count(bool $verifiedOnly = false): int
    {
        $sql = "SELECT COUNT(*) FROM matches WHERE deleted_at IS NULL";

        if ($verifiedOnly) {
            $sql .= " AND verified = 1";
        }

        return (int) $this->db->fetchColumn($sql);
    }

    /**
     * Get recent matches
     *
     * @param int $limit Number of matches to return
     * @return array Array of recent matches
     */
    public function getRecent(int $limit = 10): array
    {
        $sql = "SELECT id, created_at, title, verified
                FROM matches
                WHERE deleted_at IS NULL
                ORDER BY created_at DESC
                LIMIT :limit";

        return $this->db->fetchAll($sql, ['limit' => $limit]);
    }

    /**
     * Search matches by title
     *
     * @param string $query Search query
     * @param int $limit Maximum results
     * @return array Array of matching matches
     */
    public function searchByTitle(string $query, int $limit = 50): array
    {
        $sql = "SELECT id, created_at, title, verified
                FROM matches
                WHERE deleted_at IS NULL
                AND title LIKE :query
                ORDER BY created_at DESC
                LIMIT :limit";

        return $this->db->fetchAll($sql, [
            'query' => '%' . $query . '%',
            'limit' => $limit
        ]);
    }

    /**
     * Get match in file format (for API compatibility)
     * This returns match data in the same format as the JSON files
     *
     * @param string $id Match ID
     * @return array|null Match data in file format
     */
    public function getInFileFormat(string $id): ?array
    {
        $match = $this->findById($id);

        if (!$match) {
            return null;
        }

        // Convert database format to file format
        return [
            'meta' => [
                'title' => $match['title'],
                'oversPerSide' => $match['overs_per_side'],
                'wicketsLimit' => $match['wickets_limit']
            ],
            'teams' => $match['teams'],
            'innings' => $match['innings'],
            '__saved_at' => $match['created_at'],
            '__version' => $match['version'],
            '__verified' => (bool) $match['verified'],
            '__verified_at' => $match['verified_at'],
            '__verified_by' => $match['verified_by']
        ];
    }

    /**
     * Get all matches in list format (for API compatibility)
     * This returns the format expected by the matches list API
     *
     * @param int $limit Maximum number of matches
     * @param int $offset Offset for pagination
     * @return array Array of match list items
     */
    public function getAllInListFormat(int $limit = 100, int $offset = 0): array
    {
        $matches = $this->getMatchesList($limit, $offset);
        $result = [];

        foreach ($matches as $match) {
            // Load full match data to get meta info
            $fullMatch = $this->findById($match['id']);

            if ($fullMatch) {
                $result[] = [
                    'id' => $match['id'],
                    'ts' => $match['created_at'],
                    'title' => $match['title'],
                    'payload' => [
                        'meta' => [
                            'title' => $fullMatch['title'],
                            'oversPerSide' => $fullMatch['overs_per_side'],
                            'wicketsLimit' => $fullMatch['wickets_limit']
                        ],
                        'teams' => $fullMatch['teams'],
                        'innings' => $fullMatch['innings']
                    ]
                ];
            }
        }

        return $result;
    }

    /**
     * Decode JSON fields in match data
     *
     * @param array $match Match data from database
     * @return array Match data with decoded JSON fields
     */
    private function decodeMatchData(array $match): array
    {
        // Decode JSON fields
        $match['teams'] = json_decode($match['teams'], true) ?? [];
        $match['innings'] = json_decode($match['innings'], true) ?? [];

        // Convert verified to boolean
        $match['verified'] = (bool) $match['verified'];

        return $match;
    }
}
