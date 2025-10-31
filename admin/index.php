<?php
declare(strict_types=1);
require_once 'auth.php';
requireAdmin();

$dataDir = __DIR__ . '/../data';
$liveDir = __DIR__ . '/../data/live';

// Count matches
$matchFiles = glob($dataDir . '/*.json') ?: [];
$totalMatches = count($matchFiles);

// Count active live sessions
$liveSessions = 0;
if (is_dir($liveDir)) {
    $liveFiles = glob($liveDir . '/*.json') ?: [];
    foreach ($liveFiles as $file) {
        $data = json_decode(file_get_contents($file), true);
        if ($data && ($data['active'] ?? false)) {
            $liveSessions++;
        }
    }
}

// Get recent matches
$recentMatches = [];
if (count($matchFiles) > 0) {
    usort($matchFiles, fn($a, $b) => filemtime($b) <=> filemtime($a));
    $recentMatches = array_slice($matchFiles, 0, 5);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StumpVision Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <h1>Dashboard</h1>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🏏</div>
                <div class="stat-value"><?php echo $totalMatches; ?></div>
                <div class="stat-label">Total Matches</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🔴</div>
                <div class="stat-value"><?php echo $liveSessions; ?></div>
                <div class="stat-label">Active Live Sessions</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-value">0</div>
                <div class="stat-label">Registered Players</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value">0</div>
                <div class="stat-label">Verified Matches</div>
            </div>
        </div>

        <div class="grid-2">
            <div class="card">
                <h2>Recent Matches</h2>
                <?php if (count($recentMatches) > 0): ?>
                    <div class="match-list">
                        <?php foreach ($recentMatches as $file): ?>
                            <?php
                            $id = basename($file, '.json');
                            $data = json_decode(file_get_contents($file), true);
                            $title = $data['meta']['title'] ?? 'Unknown Match';
                            $date = date('M j, Y g:i A', filemtime($file));
                            ?>
                            <div class="match-item">
                                <div class="match-info">
                                    <strong><?php echo htmlspecialchars($title); ?></strong>
                                    <span class="match-date"><?php echo $date; ?></span>
                                </div>
                                <a href="matches.php?view=<?php echo urlencode($id); ?>" class="btn-small">View</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-state">No matches yet</p>
                <?php endif; ?>
                <a href="matches.php" class="btn-secondary">View All Matches</a>
            </div>

            <div class="card">
                <h2>Quick Actions</h2>
                <div class="action-list">
                    <a href="matches.php" class="action-btn">
                        <span class="action-icon">🏏</span>
                        <div>
                            <strong>Manage Matches</strong>
                            <span>View, verify, and delete matches</span>
                        </div>
                    </a>

                    <a href="players.php" class="action-btn">
                        <span class="action-icon">👤</span>
                        <div>
                            <strong>Player Registry</strong>
                            <span>Manage registered players</span>
                        </div>
                    </a>

                    <a href="stats.php" class="action-btn">
                        <span class="action-icon">📊</span>
                        <div>
                            <strong>View Stats</strong>
                            <span>Aggregate player statistics</span>
                        </div>
                    </a>

                    <a href="live-sessions.php" class="action-btn">
                        <span class="action-icon">🔴</span>
                        <div>
                            <strong>Live Sessions</strong>
                            <span>Manage active live score sharing</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
