<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/lib/InstallCheck.php';
use StumpVision\InstallCheck;
InstallCheck::requireInstalled();

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
                                    <li style="padding: 4px 0;">
                                        <?php
                                        $playerName = is_array($player) ? ($player['name'] ?? $player['id'] ?? 'Unknown') : $player;
                                        echo htmlspecialchars($playerName);
                                        ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <div class="card">
                        <h2><?php echo htmlspecialchars($teams[1]['name'] ?? 'Team 2'); ?></h2>
                        <?php if (isset($teams[1]['players'])): ?>
                            <ul style="list-style: none; padding: 0;">
                                <?php foreach ($teams[1]['players'] as $player): ?>
                                    <li style="padding: 4px 0;">
                                        <?php
                                        $playerName = is_array($player) ? ($player['name'] ?? $player['id'] ?? 'Unknown') : $player;
                                        echo htmlspecialchars($playerName);
                                        ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (count($innings) > 0): ?>
                <div class="card" style="margin-top: 20px;">
                    <h2>Match Results</h2>

                    <?php
                    // Calculate match result
                    $inn1 = $innings[0] ?? null;
                    $inn2 = $innings[1] ?? null;
                    $resultText = 'Match Complete';
                    $winnerTeamIdx = null;

                    if ($inn1 && $inn2) {
                        $team1Runs = $inn1['runs'] ?? 0;
                        $team2Runs = $inn2['runs'] ?? 0;

                        if ($team2Runs > $team1Runs) {
                            $wicketsLeft = ($meta['wicketsLimit'] ?? 10) - ($inn2['wickets'] ?? 0);
                            $winnerTeamIdx = $inn2['batting'] ?? null;
                            $resultText = ($winnerTeamIdx !== null && isset($teams[$winnerTeamIdx]['name']))
                                ? htmlspecialchars($teams[$winnerTeamIdx]['name']) . " won by $wicketsLeft wickets"
                                : "Match Complete";
                        } elseif ($team1Runs > $team2Runs) {
                            $margin = $team1Runs - $team2Runs;
                            $winnerTeamIdx = $inn1['batting'] ?? null;
                            $resultText = ($winnerTeamIdx !== null && isset($teams[$winnerTeamIdx]['name']))
                                ? htmlspecialchars($teams[$winnerTeamIdx]['name']) . " won by $margin runs"
                                : "Match Complete";
                        } else {
                            $resultText = 'Match Tied';
                        }
                    } elseif ($inn1) {
                        $resultText = 'First Innings Complete';
                    }
                    ?>

                    <div style="text-align: center; padding: 20px; background: var(--accent-light); border-radius: 8px; margin-bottom: 20px;">
                        <h3 style="margin: 0; color: var(--accent); font-size: 24px;"><?php echo $resultText; ?></h3>
                    </div>

                    <?php if ($inn1): ?>
                        <?php
                        $battingTeamIdx1 = $inn1['batting'] ?? 0;
                        $bowlingTeamIdx1 = $inn1['bowling'] ?? 1;
                        $battingTeam1 = $teams[$battingTeamIdx1] ?? ['name' => 'Team ' . ($battingTeamIdx1 + 1)];
                        $bowlingTeam1 = $teams[$bowlingTeamIdx1] ?? ['name' => 'Team ' . ($bowlingTeamIdx1 + 1)];

                        $runs1 = $inn1['runs'] ?? 0;
                        $wickets1 = $inn1['wickets'] ?? 0;
                        $balls1 = $inn1['balls'] ?? 0;
                        $overs1 = floor($balls1 / 6) . '.' . ($balls1 % 6);
                        ?>

                        <div style="margin-bottom: 30px;">
                            <h3 style="color: var(--accent); border-bottom: 2px solid var(--line); padding-bottom: 8px; margin-bottom: 16px;">
                                First Innings - <?php echo htmlspecialchars($battingTeam1['name']); ?>
                                <?php if ($winnerTeamIdx === $battingTeamIdx1): ?>
                                    <span style="color: var(--success); font-size: 16px;">WINNER</span>
                                <?php endif; ?>
                            </h3>

                            <div style="text-align: center; padding: 16px; background: var(--card-bg); border: 2px solid var(--line); border-radius: 8px; margin-bottom: 16px;">
                                <div style="font-size: 32px; font-weight: bold; color: var(--ink);">
                                    <?php echo $runs1; ?>/<?php echo $wickets1; ?>
                                </div>
                                <div style="color: var(--muted); margin-top: 4px;">
                                    (<?php echo $overs1; ?> overs)
                                </div>
                            </div>

                            <h4 style="margin: 20px 0 12px 0;">Batting</h4>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Batsman</th>
                                            <th>R</th>
                                            <th>B</th>
                                            <th>4s</th>
                                            <th>6s</th>
                                            <th>SR</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $batStats1 = $inn1['batStats'] ?? [];
                                        if (count($batStats1) > 0):
                                            foreach ($batStats1 as $bat):
                                                $sr = ($bat['balls'] ?? 0) > 0 ? number_format((($bat['runs'] ?? 0) / $bat['balls']) * 100, 1) : '0.0';
                                                $rowStyle = ($bat['runs'] ?? 0) >= 50 ? 'background-color: var(--accent-light);' : '';
                                        ?>
                                            <tr style="<?php echo $rowStyle; ?>">
                                                <td>
                                                    <?php echo htmlspecialchars($bat['name'] ?? 'Unknown'); ?>
                                                    <?php if (!($bat['out'] ?? false)): ?>
                                                        <small style="color: var(--muted);"> (not out)</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><strong><?php echo $bat['runs'] ?? 0; ?></strong></td>
                                                <td><?php echo $bat['balls'] ?? 0; ?></td>
                                                <td><?php echo $bat['fours'] ?? 0; ?></td>
                                                <td><?php echo $bat['sixes'] ?? 0; ?></td>
                                                <td><?php echo $sr; ?></td>
                                            </tr>
                                        <?php
                                            endforeach;
                                        else:
                                        ?>
                                            <tr><td colspan="6" style="text-align: center; color: var(--muted);">No batting data</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <h4 style="margin: 20px 0 12px 0;">Bowling - <?php echo htmlspecialchars($bowlingTeam1['name']); ?></h4>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Bowler</th>
                                            <th>O</th>
                                            <th>R</th>
                                            <th>W</th>
                                            <th>Econ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $bowlStats1 = $inn1['bowlStats'] ?? [];
                                        if (count($bowlStats1) > 0):
                                            foreach ($bowlStats1 as $bowl):
                                                $balls = $bowl['balls'] ?? 0;
                                                $overs = floor($balls / 6) . '.' . ($balls % 6);
                                                $totalOvers = $balls / 6;
                                                $econ = $totalOvers > 0 ? number_format(($bowl['runs'] ?? 0) / $totalOvers, 2) : '0.00';
                                                $rowStyle = ($bowl['wickets'] ?? 0) >= 3 ? 'background-color: var(--accent-light);' : '';
                                        ?>
                                            <tr style="<?php echo $rowStyle; ?>">
                                                <td><?php echo htmlspecialchars($bowl['name'] ?? 'Unknown'); ?></td>
                                                <td><?php echo $overs; ?></td>
                                                <td><?php echo $bowl['runs'] ?? 0; ?></td>
                                                <td><strong><?php echo $bowl['wickets'] ?? 0; ?></strong></td>
                                                <td><?php echo $econ; ?></td>
                                            </tr>
                                        <?php
                                            endforeach;
                                        else:
                                        ?>
                                            <tr><td colspan="5" style="text-align: center; color: var(--muted);">No bowling data</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php
                            $extras1 = $inn1['extras'] ?? ['nb' => 0, 'wd' => 0, 'b' => 0, 'lb' => 0];
                            $totalExtras1 = ($extras1['nb'] ?? 0) + ($extras1['wd'] ?? 0) + ($extras1['b'] ?? 0) + ($extras1['lb'] ?? 0);
                            ?>
                            <h4 style="margin: 20px 0 12px 0;">Extras (<?php echo $totalExtras1; ?>)</h4>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px;">
                                <div style="padding: 8px; background: var(--card-bg); border: 1px solid var(--line); border-radius: 4px;">
                                    <span>No Balls: </span><strong><?php echo $extras1['nb'] ?? 0; ?></strong>
                                </div>
                                <div style="padding: 8px; background: var(--card-bg); border: 1px solid var(--line); border-radius: 4px;">
                                    <span>Wides: </span><strong><?php echo $extras1['wd'] ?? 0; ?></strong>
                                </div>
                                <div style="padding: 8px; background: var(--card-bg); border: 1px solid var(--line); border-radius: 4px;">
                                    <span>Byes: </span><strong><?php echo $extras1['b'] ?? 0; ?></strong>
                                </div>
                                <div style="padding: 8px; background: var(--card-bg); border: 1px solid var(--line); border-radius: 4px;">
                                    <span>Leg Byes: </span><strong><?php echo $extras1['lb'] ?? 0; ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($inn2): ?>
                        <?php
                        $battingTeamIdx2 = $inn2['batting'] ?? 1;
                        $bowlingTeamIdx2 = $inn2['bowling'] ?? 0;
                        $battingTeam2 = $teams[$battingTeamIdx2] ?? ['name' => 'Team ' . ($battingTeamIdx2 + 1)];
                        $bowlingTeam2 = $teams[$bowlingTeamIdx2] ?? ['name' => 'Team ' . ($bowlingTeamIdx2 + 1)];

                        $runs2 = $inn2['runs'] ?? 0;
                        $wickets2 = $inn2['wickets'] ?? 0;
                        $balls2 = $inn2['balls'] ?? 0;
                        $overs2 = floor($balls2 / 6) . '.' . ($balls2 % 6);
                        ?>

                        <div style="margin-bottom: 20px;">
                            <h3 style="color: var(--accent); border-bottom: 2px solid var(--line); padding-bottom: 8px; margin-bottom: 16px;">
                                Second Innings - <?php echo htmlspecialchars($battingTeam2['name']); ?>
                                <?php if ($winnerTeamIdx === $battingTeamIdx2): ?>
                                    <span style="color: var(--success); font-size: 16px;">WINNER</span>
                                <?php endif; ?>
                            </h3>

                            <div style="text-align: center; padding: 16px; background: var(--card-bg); border: 2px solid var(--line); border-radius: 8px; margin-bottom: 16px;">
                                <div style="font-size: 32px; font-weight: bold; color: var(--ink);">
                                    <?php echo $runs2; ?>/<?php echo $wickets2; ?>
                                </div>
                                <div style="color: var(--muted); margin-top: 4px;">
                                    (<?php echo $overs2; ?> overs)
                                </div>
                            </div>

                            <h4 style="margin: 20px 0 12px 0;">Batting</h4>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Batsman</th>
                                            <th>R</th>
                                            <th>B</th>
                                            <th>4s</th>
                                            <th>6s</th>
                                            <th>SR</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $batStats2 = $inn2['batStats'] ?? [];
                                        if (count($batStats2) > 0):
                                            foreach ($batStats2 as $bat):
                                                $sr = ($bat['balls'] ?? 0) > 0 ? number_format((($bat['runs'] ?? 0) / $bat['balls']) * 100, 1) : '0.0';
                                                $rowStyle = ($bat['runs'] ?? 0) >= 50 ? 'background-color: var(--accent-light);' : '';
                                        ?>
                                            <tr style="<?php echo $rowStyle; ?>">
                                                <td>
                                                    <?php echo htmlspecialchars($bat['name'] ?? 'Unknown'); ?>
                                                    <?php if (!($bat['out'] ?? false)): ?>
                                                        <small style="color: var(--muted);"> (not out)</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><strong><?php echo $bat['runs'] ?? 0; ?></strong></td>
                                                <td><?php echo $bat['balls'] ?? 0; ?></td>
                                                <td><?php echo $bat['fours'] ?? 0; ?></td>
                                                <td><?php echo $bat['sixes'] ?? 0; ?></td>
                                                <td><?php echo $sr; ?></td>
                                            </tr>
                                        <?php
                                            endforeach;
                                        else:
                                        ?>
                                            <tr><td colspan="6" style="text-align: center; color: var(--muted);">No batting data</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <h4 style="margin: 20px 0 12px 0;">Bowling - <?php echo htmlspecialchars($bowlingTeam2['name']); ?></h4>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Bowler</th>
                                            <th>O</th>
                                            <th>R</th>
                                            <th>W</th>
                                            <th>Econ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $bowlStats2 = $inn2['bowlStats'] ?? [];
                                        if (count($bowlStats2) > 0):
                                            foreach ($bowlStats2 as $bowl):
                                                $balls = $bowl['balls'] ?? 0;
                                                $overs = floor($balls / 6) . '.' . ($balls % 6);
                                                $totalOvers = $balls / 6;
                                                $econ = $totalOvers > 0 ? number_format(($bowl['runs'] ?? 0) / $totalOvers, 2) : '0.00';
                                                $rowStyle = ($bowl['wickets'] ?? 0) >= 3 ? 'background-color: var(--accent-light);' : '';
                                        ?>
                                            <tr style="<?php echo $rowStyle; ?>">
                                                <td><?php echo htmlspecialchars($bowl['name'] ?? 'Unknown'); ?></td>
                                                <td><?php echo $overs; ?></td>
                                                <td><?php echo $bowl['runs'] ?? 0; ?></td>
                                                <td><strong><?php echo $bowl['wickets'] ?? 0; ?></strong></td>
                                                <td><?php echo $econ; ?></td>
                                            </tr>
                                        <?php
                                            endforeach;
                                        else:
                                        ?>
                                            <tr><td colspan="5" style="text-align: center; color: var(--muted);">No bowling data</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php
                            $extras2 = $inn2['extras'] ?? ['nb' => 0, 'wd' => 0, 'b' => 0, 'lb' => 0];
                            $totalExtras2 = ($extras2['nb'] ?? 0) + ($extras2['wd'] ?? 0) + ($extras2['b'] ?? 0) + ($extras2['lb'] ?? 0);
                            ?>
                            <h4 style="margin: 20px 0 12px 0;">Extras (<?php echo $totalExtras2; ?>)</h4>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px;">
                                <div style="padding: 8px; background: var(--card-bg); border: 1px solid var(--line); border-radius: 4px;">
                                    <span>No Balls: </span><strong><?php echo $extras2['nb'] ?? 0; ?></strong>
                                </div>
                                <div style="padding: 8px; background: var(--card-bg); border: 1px solid var(--line); border-radius: 4px;">
                                    <span>Wides: </span><strong><?php echo $extras2['wd'] ?? 0; ?></strong>
                                </div>
                                <div style="padding: 8px; background: var(--card-bg); border: 1px solid var(--line); border-radius: 4px;">
                                    <span>Byes: </span><strong><?php echo $extras2['b'] ?? 0; ?></strong>
                                </div>
                                <div style="padding: 8px; background: var(--card-bg); border: 1px solid var(--line); border-radius: 4px;">
                                    <span>Leg Byes: </span><strong><?php echo $extras2['lb'] ?? 0; ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
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
