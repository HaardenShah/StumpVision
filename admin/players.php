<?php
declare(strict_types=1);
require_once 'auth.php';
requireAdmin();
checkPasswordChangeRequired();

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token'])) {
        $message = 'CSRF token missing';
        $messageType = 'error';
    } elseif (!validateAdminCsrfToken($_POST['csrf_token'])) {
        $message = 'Invalid CSRF token. Please refresh the page and try again.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $name = trim($_POST['name'] ?? '');
            $team = trim($_POST['team'] ?? '');
            $playerType = trim($_POST['player_type'] ?? '');

            if (empty($name)) {
                $message = 'Player name is required';
                $messageType = 'error';
            } else {
                // Load current players
                $playersFile = __DIR__ . '/../data/players.json';
                $players = [];
                if (is_file($playersFile)) {
                    $players = json_decode(file_get_contents($playersFile), true) ?: [];
                }

                // Check if player name already exists
                $nameSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
                $nameExists = false;
                foreach ($players as $p) {
                    if (strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $p['name'])) === $nameSlug) {
                        $nameExists = true;
                        break;
                    }
                }

                if ($nameExists) {
                    $message = 'A player with this name already exists';
                    $messageType = 'error';
                } else {
                    // Generate UUID
                    $playerId = sprintf(
                        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff) | 0x4000,
                        mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0xffff)
                    );

                    // Generate player code
                    $parts = preg_split('/\s+/', trim($name));
                    $firstName = $parts[0] ?? '';
                    $lastName = $parts[count($parts) - 1] ?? '';
                    $prefix = strtoupper(
                        substr($firstName, 0, 2) .
                        substr($lastName, 0, 2)
                    );
                    if (strlen($prefix) < 2) {
                        $prefix = strtoupper(substr($name, 0, 4));
                    }
                    $prefix = str_pad($prefix, 4, 'X');

                    // Generate unique code
                    $codeUnique = false;
                    $attempts = 0;
                    while (!$codeUnique && $attempts < 100) {
                        $number = str_pad((string)mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
                        $playerCode = $prefix . '-' . $number;

                        $codeUnique = true;
                        foreach ($players as $p) {
                            if (($p['code'] ?? '') === $playerCode) {
                                $codeUnique = false;
                                break;
                            }
                        }
                        $attempts++;
                    }

                    if (!$codeUnique) {
                        $playerCode = $prefix . '-' . substr((string)time(), -4);
                    }

                    // Add new player
                    $players[$playerId] = [
                        'id' => $playerId,
                        'name' => $name,
                        'code' => $playerCode,
                        'team' => $team,
                        'player_type' => $playerType,
                        'registered_at' => time(),
                        'registered_by' => $_SESSION['admin_username'] ?? 'admin'
                    ];

                    // Save players
                    $dataDir = __DIR__ . '/../data';
                    if (!is_dir($dataDir)) {
                        mkdir($dataDir, 0755, true);
                    }

                    if (file_put_contents($playersFile, json_encode($players, JSON_PRETTY_PRINT)) !== false) {
                        $message = 'Player registered successfully with code: ' . $playerCode;
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to save player data';
                        $messageType = 'error';
                    }
                }
            }
        } elseif ($action === 'update') {
            $playerId = trim($_POST['player_id'] ?? '');
            $team = trim($_POST['team'] ?? '');
            $playerType = trim($_POST['player_type'] ?? '');

            if (empty($playerId)) {
                $message = 'Player ID is required';
                $messageType = 'error';
            } else {
                // Load current players
                $playersFile = __DIR__ . '/../data/players.json';
                $players = [];
                if (is_file($playersFile)) {
                    $players = json_decode(file_get_contents($playersFile), true) ?: [];
                }

                if (!isset($players[$playerId])) {
                    $message = 'Player not found';
                    $messageType = 'error';
                } else {
                    // Update player
                    $players[$playerId]['team'] = $team;
                    $players[$playerId]['player_type'] = $playerType;
                    $players[$playerId]['updated_at'] = time();

                    // Save players
                    if (file_put_contents($playersFile, json_encode($players, JSON_PRETTY_PRINT)) !== false) {
                        $message = 'Player updated successfully';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to save player data';
                        $messageType = 'error';
                    }
                }
            }
        }
    }
}

// Load players
$playersFile = __DIR__ . '/../data/players.json';
$players = [];
if (is_file($playersFile)) {
    $players = json_decode(file_get_contents($playersFile), true) ?: [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Registry - StumpVision Admin</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <h1>Player Registry</h1>

        <div style="margin-bottom: 20px; padding: 15px; background: var(--bg-secondary, #1a1a1a); border-left: 3px solid var(--accent, #4CAF50); border-radius: 4px;">
            <p style="margin: 0 0 10px 0;"><strong>Player Registration & Verification</strong></p>
            <p style="margin: 0; color: var(--muted); line-height: 1.6;">
                Register official players here. Each player receives a unique <strong>Player Code</strong> (e.g., JOSM-1234) that they can use during match setup to verify their identity.
                Only stats from verified matches with verified players will count toward official career totals.
            </p>
            <p style="margin: 10px 0 0 0; color: var(--muted); line-height: 1.6;">
                <strong>How it works:</strong> During match setup, players can enter their code to link their performance. Without a code, they're recorded as "guest players" for pickup games.
            </p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="grid-2">
            <div class="card">
                <h2>Register New Player</h2>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo getAdminCsrfToken(); ?>">
                    <input type="hidden" name="action" value="add">

                    <div class="form-group">
                        <label for="name">Player Name *</label>
                        <input type="text" id="name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="team">Team (Optional)</label>
                        <input type="text" id="team" name="team" placeholder="e.g., Mumbai Indians, Team A">
                    </div>

                    <div class="form-group">
                        <label for="player_type">Player Type (Optional)</label>
                        <select id="player_type" name="player_type">
                            <option value="">-- Select Type --</option>
                            <option value="Right-Hand Batsman">Right-Hand Batsman</option>
                            <option value="Left-Hand Batsman">Left-Hand Batsman</option>
                            <option value="Fast Bowler">Fast Bowler</option>
                            <option value="Spin Bowler">Spin Bowler</option>
                            <option value="Wicket-Keeper">Wicket-Keeper</option>
                            <option value="All-Rounder">All-Rounder</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-primary">Register Player</button>
                </form>
            </div>

            <div class="card">
                <h2>Registered Players (<?php echo count($players); ?>)</h2>

                <?php if (count($players) > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Player Code</th>
                                    <th>Team</th>
                                    <th>Player Type</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($players as $player): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($player['name']); ?></strong></td>
                                        <td>
                                            <code style="background: var(--bg-secondary, #1a1a1a); padding: 4px 8px; border-radius: 4px; font-family: monospace; font-weight: bold; color: var(--accent, #4CAF50);">
                                                <?php echo htmlspecialchars($player['code'] ?? 'N/A'); ?>
                                            </code>
                                        </td>
                                        <td><?php echo htmlspecialchars($player['team'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($player['player_type'] ?? '-'); ?></td>
                                        <td><?php echo date('M j, Y', $player['registered_at'] ?? time()); ?></td>
                                        <td>
                                            <div class="actions">
                                                <button class="btn-small" onclick="editPlayer('<?php echo htmlspecialchars($player['id']); ?>', '<?php echo htmlspecialchars($player['name']); ?>', '<?php echo htmlspecialchars($player['team'] ?? ''); ?>', '<?php echo htmlspecialchars($player['player_type'] ?? ''); ?>')">Edit</button>
                                                <button class="btn-small" onclick="deletePlayer('<?php echo htmlspecialchars($player['id']); ?>', '<?php echo htmlspecialchars($player['name']); ?>')">Delete</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="empty-state">No players registered yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Player Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: var(--bg-primary, #121212); padding: 30px; border-radius: 8px; max-width: 500px; width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.5);">
            <h2 style="margin-top: 0;">Edit Player</h2>
            <form method="POST" action="" id="editForm">
                <input type="hidden" name="csrf_token" value="<?php echo getAdminCsrfToken(); ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="player_id" id="edit_player_id">

                <div class="form-group">
                    <label>Player Name</label>
                    <input type="text" id="edit_player_name" disabled style="background: var(--bg-secondary, #1a1a1a); cursor: not-allowed;">
                    <small style="color: var(--muted); display: block; margin-top: 5px;">Player name cannot be changed</small>
                </div>

                <div class="form-group">
                    <label for="edit_team">Team</label>
                    <input type="text" id="edit_team" name="team" placeholder="e.g., Mumbai Indians, Team A">
                </div>

                <div class="form-group">
                    <label for="edit_player_type">Player Type</label>
                    <select id="edit_player_type" name="player_type">
                        <option value="">-- Select Type --</option>
                        <option value="Right-Hand Batsman">Right-Hand Batsman</option>
                        <option value="Left-Hand Batsman">Left-Hand Batsman</option>
                        <option value="Fast Bowler">Fast Bowler</option>
                        <option value="Spin Bowler">Spin Bowler</option>
                        <option value="Wicket-Keeper">Wicket-Keeper</option>
                        <option value="All-Rounder">All-Rounder</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn-primary">Save Changes</button>
                    <button type="button" class="btn-small" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editPlayer(playerId, playerName, team, playerType) {
            document.getElementById('edit_player_id').value = playerId;
            document.getElementById('edit_player_name').value = playerName;
            document.getElementById('edit_team').value = team;
            document.getElementById('edit_player_type').value = playerType;

            const modal = document.getElementById('editModal');
            modal.style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal on outside click
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });

        async function deletePlayer(playerId, playerName) {
            if (!confirm(`Delete player "${playerName}"? This cannot be undone.`)) {
                return;
            }

            try {
                const response = await fetch('../api/players.php?action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: playerId })
                });

                const result = await response.json();
                if (result.ok) {
                    window.location.reload();
                } else {
                    alert('Failed to delete player: ' + (result.err || 'unknown error'));
                }
            } catch (err) {
                alert('Error: ' + err.message);
            }
        }
    </script>
</body>
</html>
