<?php
declare(strict_types=1);
require_once 'auth.php';
requireAdmin();

$liveDir = __DIR__ . '/../data/live';
$message = '';
$messageType = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (!validateAdminCsrfToken($_POST['csrf_token'])) {
        $message = 'Invalid CSRF token';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        $sessionId = $_POST['session_id'] ?? '';

        if ($action === 'stop' && $sessionId) {
            $file = $liveDir . '/' . basename($sessionId) . '.json';
            if (is_file($file)) {
                $data = json_decode(file_get_contents($file), true);
                $data['active'] = false;
                $data['stopped_at'] = time();
                $data['stopped_by'] = $_SESSION['admin_username'];
                if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT))) {
                    $message = 'Live session stopped successfully';
                    $messageType = 'success';
                }
            }
        } elseif ($action === 'delete' && $sessionId) {
            $file = $liveDir . '/' . basename($sessionId) . '.json';
            if (is_file($file) && unlink($file)) {
                $message = 'Live session deleted successfully';
                $messageType = 'success';
            }
        }
    }
}

// Get all live sessions
$sessions = [];
if (is_dir($liveDir)) {
    $files = glob($liveDir . '/*.json') ?: [];
    foreach ($files as $file) {
        $data = json_decode(file_get_contents($file), true);
        if ($data) {
            $sessions[] = [
                'id' => basename($file, '.json'),
                'data' => $data,
                'created' => $data['created_at'] ?? filemtime($file)
            ];
        }
    }
}

// Sort by creation time (newest first)
usort($sessions, fn($a, $b) => $b['created'] <=> $a['created']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Sessions - StumpVision Admin</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <h1>Live Score Sharing Sessions</h1>

        <p style="margin-bottom: 20px; color: var(--muted);">
            Manage active and past live score sharing sessions. You can stop or delete sessions from here.
        </p>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>All Sessions (<?php echo count($sessions); ?>)</h2>

            <?php if (count($sessions) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Session ID</th>
                                <th>Match ID</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Link</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $session): ?>
                                <?php
                                $data = $session['data'];
                                $isActive = $data['active'] ?? false;
                                $liveUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') .
                                          '://' . $_SERVER['HTTP_HOST'] .
                                          dirname(dirname($_SERVER['PHP_SELF'])) . '/live.php?id=' . $session['id'];
                                ?>
                                <tr>
                                    <td>
                                        <span style="font-family: monospace; font-size: 12px;">
                                            <?php echo htmlspecialchars($session['id']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-family: monospace; font-size: 12px;">
                                            <?php echo htmlspecialchars($data['match_id'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($isActive): ?>
                                            <span class="badge badge-success">● Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">● Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y g:i A', $session['created']); ?></td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($liveUrl); ?>" target="_blank" class="btn-small">View</a>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <?php if ($isActive): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo getAdminCsrfToken(); ?>">
                                                    <input type="hidden" name="action" value="stop">
                                                    <input type="hidden" name="session_id" value="<?php echo htmlspecialchars($session['id']); ?>">
                                                    <button type="submit" class="btn-small" style="background: var(--warning);">Stop</button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo getAdminCsrfToken(); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="session_id" value="<?php echo htmlspecialchars($session['id']); ?>">
                                                <button type="submit" class="btn-small" style="background: var(--danger);" onclick="return confirm('Delete this session?');">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty-state">No live sessions found</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
