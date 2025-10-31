<?php
declare(strict_types=1);
require_once 'auth.php';
requireAdmin();

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (!validateAdminCsrfToken($_POST['csrf_token'])) {
        $message = 'Invalid CSRF token';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $name = trim($_POST['name'] ?? '');
            $team = trim($_POST['team'] ?? '');

            if (empty($name)) {
                $message = 'Player name is required';
                $messageType = 'error';
            } else {
                $response = file_get_contents('http://localhost' . $_SERVER['REQUEST_URI'] . '/../api/players.php?action=add', false, stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => 'Content-Type: application/json',
                        'content' => json_encode(['name' => $name, 'team' => $team])
                    ]
                ]));

                $result = json_decode($response, true);
                if ($result['ok'] ?? false) {
                    $message = 'Player registered successfully';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to register player: ' . ($result['err'] ?? 'unknown error');
                    $messageType = 'error';
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

        <p style="margin-bottom: 20px; color: var(--muted);">
            Register official players here. Only stats from verified matches with registered players will count toward their career totals.
        </p>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="grid-2">
            <div class="card">
                <h2>Register New Player</h2>
                <form method="POST">
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
                                    <th>Team</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($players as $player): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($player['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($player['team'] ?? '-'); ?></td>
                                        <td><?php echo date('M j, Y', $player['registered_at'] ?? time()); ?></td>
                                        <td>
                                            <div class="actions">
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

    <script>
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
