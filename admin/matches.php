<?php
declare(strict_types=1);
require_once 'auth.php';
require_once __DIR__ . '/../api/lib/Database.php';
require_once __DIR__ . '/../api/lib/repositories/MatchRepository.php';

use StumpVision\Repositories\MatchRepository;

requireAdmin();
checkPasswordChangeRequired();

$repo = new MatchRepository();
$message = '';
$messageType = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (!validateAdminCsrfToken($_POST['csrf_token'])) {
        $message = 'Invalid CSRF token';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        $matchId = $_POST['match_id'] ?? '';

        if ($action === 'delete' && $matchId) {
            if ($repo->delete($matchId) > 0) {
                $message = 'Match deleted successfully';
                $messageType = 'success';
            } else {
                $message = 'Failed to delete match';
                $messageType = 'error';
            }
        } elseif ($action === 'verify' && $matchId) {
            $username = $_SESSION['admin_username'] ?? 'admin';
            if ($repo->verify($matchId, $username) > 0) {
                $message = 'Match verified successfully';
                $messageType = 'success';
            } else {
                $message = 'Failed to verify match';
                $messageType = 'error';
            }
        } elseif ($action === 'unverify' && $matchId) {
            if ($repo->unverify($matchId) > 0) {
                $message = 'Match unverified successfully';
                $messageType = 'success';
            } else {
                $message = 'Failed to unverify match';
                $messageType = 'error';
            }
        }
    }
}

// Get all matches
$matchList = $repo->getMatchesList(100, 0);
$matches = [];

foreach ($matchList as $match) {
    $matches[] = [
        'id' => $match['id'],
        'title' => $match['title'],
        'timestamp' => $match['created_at'],
        'verified' => (bool) $match['verified']
    ];
}

// View specific match
$viewMatch = null;
if (isset($_GET['view'])) {
    $viewId = basename($_GET['view']);
    $matchData = $repo->findById($viewId);

    if ($matchData) {
        $viewMatch = [
            'id' => $viewId,
            'data' => [
                'meta' => [
                    'title' => $matchData['title'],
                    'oversPerSide' => $matchData['overs_per_side'],
                    'wicketsLimit' => $matchData['wickets_limit']
                ],
                'teams' => $matchData['teams'],
                'innings' => $matchData['innings'],
                '__verified' => $matchData['verified'],
                '__verified_at' => $matchData['verified_at'],
                '__verified_by' => $matchData['verified_by']
            ]
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Management - StumpVision Admin</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <?php if ($viewMatch): ?>
            <div style="margin-bottom: 20px;">
                <a href="matches.php" class="btn-secondary" style="display: inline-block; width: auto; padding: 8px 16px;">← Back to All Matches</a>
            </div>

            <h1>Match Details</h1>

            <?php
            $data = $viewMatch['data'];
            $meta = $data['meta'] ?? [];
            $teams = $data['teams'] ?? [];
            $innings = $data['innings'] ?? [];
            ?>

            <div class="card" style="margin-bottom: 20px;">
                <h2><?php echo htmlspecialchars($meta['title'] ?? 'Unknown Match'); ?></h2>
                <p><strong>Match ID:</strong> <?php echo htmlspecialchars($viewMatch['id']); ?></p>
                <p><strong>Overs:</strong> <?php echo $meta['oversPerSide'] ?? 'N/A'; ?></p>
                <p><strong>Wickets Limit:</strong> <?php echo $meta['wicketsLimit'] ?? 'N/A'; ?></p>
                <p><strong>Status:</strong>
                    <?php if ($data['__verified'] ?? false): ?>
                        <span class="badge badge-success">Verified</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Unverified</span>
                    <?php endif; ?>
                </p>
            </div>

            <?php if (count($teams) >= 2): ?>
                <div class="grid-2">
                    <div class="card">
                        <h2><?php echo htmlspecialchars($teams[0]['name'] ?? 'Team 1'); ?></h2>
                        <?php if (isset($teams[0]['players'])): ?>
                            <ul style="list-style: none; padding: 0;">
                                <?php foreach ($teams[0]['players'] as $player): ?>
                                    <li style="padding: 4px 0;"><?php echo htmlspecialchars($player); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <div class="card">
                        <h2><?php echo htmlspecialchars($teams[1]['name'] ?? 'Team 2'); ?></h2>
                        <?php if (isset($teams[1]['players'])): ?>
                            <ul style="list-style: none; padding: 0;">
                                <?php foreach ($teams[1]['players'] as $player): ?>
                                    <li style="padding: 4px 0;"><?php echo htmlspecialchars($player); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card" style="margin-top: 20px;">
                <h2>Actions</h2>
                <div class="actions">
                    <?php if (!($data['__verified'] ?? false)): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo getAdminCsrfToken(); ?>">
                            <input type="hidden" name="action" value="verify">
                            <input type="hidden" name="match_id" value="<?php echo htmlspecialchars($viewMatch['id']); ?>">
                            <button type="submit" class="btn-primary btn-success" onclick="return confirm('Verify this match? Stats will count toward player totals.');">Verify Match</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo getAdminCsrfToken(); ?>">
                            <input type="hidden" name="action" value="unverify">
                            <input type="hidden" name="match_id" value="<?php echo htmlspecialchars($viewMatch['id']); ?>">
                            <button type="submit" class="btn-primary btn-danger">Unverify Match</button>
                        </form>
                    <?php endif; ?>

                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo getAdminCsrfToken(); ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="match_id" value="<?php echo htmlspecialchars($viewMatch['id']); ?>">
                        <button type="submit" class="btn-primary btn-danger" onclick="return confirm('Delete this match? This cannot be undone.');">Delete Match</button>
                    </form>
                </div>
            </div>

        <?php else: ?>
            <h1>Match Management</h1>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <h2>All Matches (<?php echo count($matches); ?>)</h2>

                <?php if (count($matches) > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Match</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($matches as $match): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($match['title']); ?></strong><br>
                                            <span style="font-size: 12px; color: var(--muted);">ID: <?php echo htmlspecialchars($match['id']); ?></span>
                                        </td>
                                        <td><?php echo date('M j, Y g:i A', $match['timestamp']); ?></td>
                                        <td>
                                            <?php if ($match['verified']): ?>
                                                <span class="badge badge-success">Verified</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Unverified</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <a href="?view=<?php echo urlencode($match['id']); ?>" class="btn-small">View</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="empty-state">No matches found</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
