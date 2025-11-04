<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/lib/InstallCheck.php';
use StumpVision\InstallCheck;
InstallCheck::requireInstalled();

require_once 'auth.php';
require_once __DIR__ . '/../api/lib/Database.php';
require_once __DIR__ . '/../api/lib/repositories/PlayerRepository.php';
require_once __DIR__ . '/../api/lib/repositories/MatchRepository.php';

use StumpVision\Repositories\PlayerRepository;
use StumpVision\Repositories\MatchRepository;

requireAdmin();
checkPasswordChangeRequired();

$playerRepo = new PlayerRepository();
$matchRepo = new MatchRepository();

// Get date filter parameters
$filter = $_GET['filter'] ?? 'all';
$customStart = $_GET['start'] ?? '';
$customEnd = $_GET['end'] ?? '';

// Calculate date ranges
$now = time();
$startDate = 0;
$endDate = $now;

switch ($filter) {
    case 'month':
        $startDate = strtotime('first day of this month 00:00:00');
        break;
    case 'year':
        $startDate = strtotime('first day of January this year 00:00:00');
        break;
    case 'custom':
        if ($customStart) {
            $startDate = strtotime($customStart . ' 00:00:00');
        }
        if ($customEnd) {
            $endDate = strtotime($customEnd . ' 23:59:59');
        }
        break;
    case 'all':
    default:
        $startDate = 0;
        break;
}

// Load registered players
$registeredPlayers = $playerRepo->getAllAsAssociativeArray();

// Aggregate stats from verified matches
$playerStats = [];
$matchDates = [];

// Load all verified matches from database
$verifiedMatches = $matchRepo->findAll(1000, 0, true);

foreach ($verifiedMatches as $matchData) {
    $matchDate = $matchData['created_at'] ?? 0;

    // Apply date filter
    if ($matchDate < $startDate || $matchDate > $endDate) {
        continue;
    }

    $matchDates[] = $matchDate;

    // Process innings
    $innings = $matchData['innings'] ?? [];
    foreach ($innings as $inning) {
        // Batting stats
        $batStats = $inning['batStats'] ?? [];
        foreach ($batStats as $stat) {
            $playerName = $stat['name'] ?? '';

            // Use player ID from match data if available
            if (!empty($stat['playerId']) && isset($stat['verified']) && $stat['verified'] === true) {
                $playerId = $stat['playerId'];
            } else {
                $playerId = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $playerName));
            }

            // Only count registered players
            if (!isset($registeredPlayers[$playerId])) {
                continue;
            }

            if (!isset($playerStats[$playerId])) {
                $canonicalName = $registeredPlayers[$playerId]['name'] ?? $playerName;

                $playerStats[$playerId] = [
                    'name' => $canonicalName,
                    'matches' => 0,
                    'innings' => 0,
                    'runs' => 0,
                    'balls' => 0,
                    'fours' => 0,
                    'sixes' => 0,
                    'highest' => 0,
                    'average' => 0,
                    'strikeRate' => 0,
                    'fifties' => 0,
                    'hundreds' => 0,
                    'ducks' => 0,
                    'wickets' => 0,
                    'bowlingRuns' => 0,
                    'bowlingBalls' => 0,
                    'economy' => 0,
                    'bestBowling' => '0/0',
                    'notOuts' => 0,
                    'thirties' => 0,
                    'maidens' => 0
                ];
            }

            $playerStats[$playerId]['matches']++;
            $playerStats[$playerId]['innings']++;
            $playerStats[$playerId]['runs'] += $stat['runs'] ?? 0;
            $playerStats[$playerId]['balls'] += $stat['balls'] ?? 0;
            $playerStats[$playerId]['fours'] += $stat['fours'] ?? 0;
            $playerStats[$playerId]['sixes'] += $stat['sixes'] ?? 0;

            if (!($stat['out'] ?? false)) {
                $playerStats[$playerId]['notOuts']++;
            }

            if (($stat['runs'] ?? 0) > $playerStats[$playerId]['highest']) {
                $playerStats[$playerId]['highest'] = $stat['runs'] ?? 0;
            }

            $runs = $stat['runs'] ?? 0;
            if ($runs >= 100) {
                $playerStats[$playerId]['hundreds']++;
            } elseif ($runs >= 50) {
                $playerStats[$playerId]['fifties']++;
            } elseif ($runs >= 30) {
                $playerStats[$playerId]['thirties']++;
            }

            if (($stat['balls'] ?? 0) > 0 && $runs == 0 && ($stat['out'] ?? false)) {
                $playerStats[$playerId]['ducks']++;
            }
        }

        // Bowling stats
        $bowlStats = $inning['bowlStats'] ?? [];
        foreach ($bowlStats as $stat) {
            $playerName = $stat['name'] ?? '';

            if (!empty($stat['playerId']) && isset($stat['verified']) && $stat['verified'] === true) {
                $playerId = $stat['playerId'];
            } else {
                $playerId = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $playerName));
            }

            if (!isset($registeredPlayers[$playerId])) {
                continue;
            }

            if (!isset($playerStats[$playerId])) {
                $canonicalName = $registeredPlayers[$playerId]['name'] ?? $playerName;

                $playerStats[$playerId] = [
                    'name' => $canonicalName,
                    'matches' => 0,
                    'innings' => 0,
                    'runs' => 0,
                    'balls' => 0,
                    'fours' => 0,
                    'sixes' => 0,
                    'highest' => 0,
                    'average' => 0,
                    'strikeRate' => 0,
                    'fifties' => 0,
                    'hundreds' => 0,
                    'ducks' => 0,
                    'wickets' => 0,
                    'bowlingRuns' => 0,
                    'bowlingBalls' => 0,
                    'economy' => 0,
                    'bestBowling' => '0/0',
                    'notOuts' => 0,
                    'thirties' => 0,
                    'maidens' => 0
                ];
            }

            $playerStats[$playerId]['wickets'] += $stat['wickets'] ?? 0;
            $playerStats[$playerId]['bowlingRuns'] += $stat['runs'] ?? 0;
            $playerStats[$playerId]['bowlingBalls'] += $stat['balls'] ?? 0;

            // Track best bowling
            $currentBest = explode('/', $playerStats[$playerId]['bestBowling']);
            $thisBowling = [($stat['wickets'] ?? 0), ($stat['runs'] ?? 0)];
            if ($thisBowling[0] > (int)$currentBest[0] ||
                ($thisBowling[0] == (int)$currentBest[0] && $thisBowling[1] < (int)$currentBest[1])) {
                $playerStats[$playerId]['bestBowling'] = $thisBowling[0] . '/' . $thisBowling[1];
            }
        }
    }
}

// Calculate derived stats
foreach ($playerStats as &$stats) {
    $dismissals = $stats['innings'] - $stats['notOuts'];
    if ($dismissals > 0) {
        $stats['average'] = round($stats['runs'] / $dismissals, 2);
    }
    if ($stats['balls'] > 0) {
        $stats['strikeRate'] = round(($stats['runs'] / $stats['balls']) * 100, 2);
    }
    if ($stats['bowlingBalls'] > 0) {
        $overs = $stats['bowlingBalls'] / 6;
        $stats['economy'] = round($stats['bowlingRuns'] / $overs, 2);
    }
}
unset($stats);

// Create different leaderboards
$battingLeaderboard = $playerStats;
uasort($battingLeaderboard, fn($a, $b) => $b['runs'] <=> $a['runs']);

$bowlingLeaderboard = array_filter($playerStats, fn($s) => $s['wickets'] > 0);
uasort($bowlingLeaderboard, fn($a, $b) => $b['wickets'] <=> $a['wickets']);

$averageLeaderboard = array_filter($playerStats, fn($s) => $s['innings'] >= 3 && $s['average'] > 0);
uasort($averageLeaderboard, fn($a, $b) => $b['average'] <=> $a['average']);

$strikeRateLeaderboard = array_filter($playerStats, fn($s) => $s['balls'] >= 30);
uasort($strikeRateLeaderboard, fn($a, $b) => $b['strikeRate'] <=> $a['strikeRate']);

$economyLeaderboard = array_filter($playerStats, fn($s) => $s['bowlingBalls'] >= 24);
uasort($economyLeaderboard, fn($a, $b) => $a['economy'] <=> $b['economy']);

// Determine period label
$periodLabel = 'All Time';
switch ($filter) {
    case 'month':
        $periodLabel = date('F Y');
        break;
    case 'year':
        $periodLabel = date('Y');
        break;
    case 'custom':
        if ($customStart && $customEnd) {
            $periodLabel = date('M j, Y', $startDate) . ' - ' . date('M j, Y', $endDate);
        }
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Statistics - StumpVision Admin</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .stats-header {
            background: linear-gradient(135deg, var(--accent), var(--success));
            color: white;
            padding: 40px 20px;
            border-radius: 16px;
            margin-bottom: 30px;
            text-align: center;
        }

        .stats-header h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
        }

        .stats-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 18px;
        }

        .filter-bar {
            background: var(--card);
            border: 2px solid var(--line);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-btn {
            padding: 10px 20px;
            border: 2px solid var(--line);
            border-radius: 8px;
            background: var(--bg);
            color: var(--ink);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .filter-btn:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .filter-btn.active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        .custom-date-inputs {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .custom-date-inputs input {
            padding: 8px 12px;
            border: 2px solid var(--line);
            border-radius: 6px;
            background: var(--bg);
            color: var(--ink);
        }

        .podium {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 40px;
            align-items: end;
        }

        .podium-place {
            background: var(--card);
            border: 2px solid var(--line);
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            position: relative;
            transition: transform 0.2s;
        }

        .podium-place:hover {
            transform: translateY(-4px);
        }

        .podium-place.first {
            order: 2;
            border-color: #FFD700;
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), var(--card));
        }

        .podium-place.second {
            order: 1;
            border-color: #C0C0C0;
        }

        .podium-place.third {
            order: 3;
            border-color: #CD7F32;
        }

        .podium-medal {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .podium-name {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--ink);
        }

        .podium-stat {
            font-size: 32px;
            font-weight: 900;
            color: var(--accent);
            margin-bottom: 4px;
        }

        .podium-label {
            font-size: 14px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card);
            border: 2px solid var(--line);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }

        .stat-card-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .stat-card-value {
            font-size: 28px;
            font-weight: 900;
            color: var(--accent);
            margin-bottom: 4px;
        }

        .stat-card-label {
            font-size: 14px;
            color: var(--muted);
            text-transform: uppercase;
        }

        .leaderboard-section {
            margin-bottom: 40px;
        }

        .leaderboard-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .leaderboard-icon {
            font-size: 28px;
        }

        .rank-badge {
            display: inline-block;
            width: 32px;
            height: 32px;
            line-height: 32px;
            border-radius: 50%;
            font-weight: 700;
            text-align: center;
        }

        .rank-1 { background: #FFD700; color: #000; }
        .rank-2 { background: #C0C0C0; color: #000; }
        .rank-3 { background: #CD7F32; color: #fff; }
        .rank-other { background: var(--line); color: var(--muted); }

        .achievement-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            margin: 0 4px;
        }

        .badge-century { background: #FFD700; color: #000; }
        .badge-fifty { background: var(--success-light); color: var(--success); }
        .badge-wickets { background: var(--danger-light); color: var(--danger); }

        .export-btn {
            padding: 12px 24px;
            background: var(--success);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            margin-left: auto;
        }

        .export-btn:hover {
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .podium {
                grid-template-columns: 1fr;
            }
            .podium-place.first,
            .podium-place.second,
            .podium-place.third {
                order: initial;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="stats-header">
            <h1>🏆 Advanced Statistics</h1>
            <p>Performance Analytics & Leaderboards - <?php echo htmlspecialchars($periodLabel); ?></p>
        </div>

        <!-- Date Filter Bar -->
        <div class="filter-bar">
            <strong>Filter by:</strong>
            <a href="?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">All Time</a>
            <a href="?filter=month" class="filter-btn <?php echo $filter === 'month' ? 'active' : ''; ?>">This Month</a>
            <a href="?filter=year" class="filter-btn <?php echo $filter === 'year' ? 'active' : ''; ?>">This Year</a>

            <form method="GET" style="display: flex; gap: 8px; margin-left: auto;">
                <input type="hidden" name="filter" value="custom">
                <div class="custom-date-inputs">
                    <input type="date" name="start" value="<?php echo htmlspecialchars($customStart); ?>" required>
                    <span>to</span>
                    <input type="date" name="end" value="<?php echo htmlspecialchars($customEnd); ?>" required>
                    <button type="submit" class="filter-btn">Apply</button>
                </div>
            </form>

            <button class="export-btn" onclick="exportStats()">📊 Export CSV</button>
        </div>

        <?php if (count($playerStats) > 0): ?>

            <!-- Stat Cards -->
            <div class="stat-cards">
                <div class="stat-card">
                    <div class="stat-card-icon">🏏</div>
                    <div class="stat-card-value"><?php echo count($matchDates); ?></div>
                    <div class="stat-card-label">Matches Played</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon">👥</div>
                    <div class="stat-card-value"><?php echo count($playerStats); ?></div>
                    <div class="stat-card-label">Active Players</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon">💯</div>
                    <div class="stat-card-value"><?php echo array_sum(array_column($playerStats, 'hundreds')); ?></div>
                    <div class="stat-card-label">Centuries Scored</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon">🎯</div>
                    <div class="stat-card-value"><?php echo array_sum(array_column($playerStats, 'wickets')); ?></div>
                    <div class="stat-card-label">Wickets Taken</div>
                </div>
            </div>

            <!-- Most Runs Podium -->
            <div class="leaderboard-section">
                <h2 class="leaderboard-title">
                    <span class="leaderboard-icon">🏏</span>
                    Top Run Scorers
                </h2>
                <div class="podium">
                    <?php
                    $topBatsmen = array_slice($battingLeaderboard, 0, 3, true);
                    $medals = ['🥇', '🥈', '🥉'];
                    $classes = ['first', 'second', 'third'];
                    $i = 0;
                    foreach ($topBatsmen as $stats):
                    ?>
                        <div class="podium-place <?php echo $classes[$i]; ?>">
                            <div class="podium-medal"><?php echo $medals[$i]; ?></div>
                            <div class="podium-name"><?php echo htmlspecialchars($stats['name']); ?></div>
                            <div class="podium-stat"><?php echo $stats['runs']; ?></div>
                            <div class="podium-label">Runs</div>
                            <div style="margin-top: 12px; font-size: 12px; color: var(--muted);">
                                Avg: <?php echo $stats['average']; ?> | SR: <?php echo $stats['strikeRate']; ?>
                            </div>
                            <?php if ($stats['hundreds'] > 0): ?>
                                <span class="achievement-badge badge-century">💯 <?php echo $stats['hundreds']; ?>x</span>
                            <?php endif; ?>
                            <?php if ($stats['fifties'] > 0): ?>
                                <span class="achievement-badge badge-fifty">50+ <?php echo $stats['fifties']; ?>x</span>
                            <?php endif; ?>
                        </div>
                    <?php $i++; endforeach; ?>
                </div>
            </div>

            <!-- Most Wickets Podium -->
            <?php if (count($bowlingLeaderboard) >= 3): ?>
            <div class="leaderboard-section">
                <h2 class="leaderboard-title">
                    <span class="leaderboard-icon">🎯</span>
                    Top Wicket Takers
                </h2>
                <div class="podium">
                    <?php
                    $topBowlers = array_slice($bowlingLeaderboard, 0, 3, true);
                    $i = 0;
                    foreach ($topBowlers as $stats):
                    ?>
                        <div class="podium-place <?php echo $classes[$i]; ?>">
                            <div class="podium-medal"><?php echo $medals[$i]; ?></div>
                            <div class="podium-name"><?php echo htmlspecialchars($stats['name']); ?></div>
                            <div class="podium-stat"><?php echo $stats['wickets']; ?></div>
                            <div class="podium-label">Wickets</div>
                            <div style="margin-top: 12px; font-size: 12px; color: var(--muted);">
                                Econ: <?php echo $stats['economy']; ?> | Best: <?php echo $stats['bestBowling']; ?>
                            </div>
                        </div>
                    <?php $i++; endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Complete Batting Leaderboard -->
            <div class="leaderboard-section">
                <div class="card">
                    <h2 class="leaderboard-title">
                        <span class="leaderboard-icon">📊</span>
                        Complete Batting Leaderboard
                    </h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Player</th>
                                    <th>Mat</th>
                                    <th>Inn</th>
                                    <th>Runs</th>
                                    <th>HS</th>
                                    <th>Avg</th>
                                    <th>SR</th>
                                    <th>100s</th>
                                    <th>50s</th>
                                    <th>4s</th>
                                    <th>6s</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $rank = 1;
                                foreach ($battingLeaderboard as $stats):
                                ?>
                                    <tr>
                                        <td>
                                            <span class="rank-badge rank-<?php echo $rank <= 3 ? $rank : 'other'; ?>">
                                                <?php echo $rank; ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($stats['name']); ?></strong></td>
                                        <td><?php echo $stats['matches']; ?></td>
                                        <td><?php echo $stats['innings']; ?></td>
                                        <td><strong><?php echo $stats['runs']; ?></strong></td>
                                        <td><?php echo $stats['highest']; ?></td>
                                        <td><?php echo $stats['average']; ?></td>
                                        <td><?php echo $stats['strikeRate']; ?></td>
                                        <td><?php echo $stats['hundreds']; ?></td>
                                        <td><?php echo $stats['fifties']; ?></td>
                                        <td><?php echo $stats['fours']; ?></td>
                                        <td><?php echo $stats['sixes']; ?></td>
                                    </tr>
                                <?php $rank++; endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Complete Bowling Leaderboard -->
            <?php if (count($bowlingLeaderboard) > 0): ?>
            <div class="leaderboard-section">
                <div class="card">
                    <h2 class="leaderboard-title">
                        <span class="leaderboard-icon">🎳</span>
                        Complete Bowling Leaderboard
                    </h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Player</th>
                                    <th>Mat</th>
                                    <th>Wkts</th>
                                    <th>Runs</th>
                                    <th>Balls</th>
                                    <th>Econ</th>
                                    <th>Best</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $rank = 1;
                                foreach ($bowlingLeaderboard as $stats):
                                ?>
                                    <tr>
                                        <td>
                                            <span class="rank-badge rank-<?php echo $rank <= 3 ? $rank : 'other'; ?>">
                                                <?php echo $rank; ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($stats['name']); ?></strong></td>
                                        <td><?php echo $stats['matches']; ?></td>
                                        <td><strong><?php echo $stats['wickets']; ?></strong></td>
                                        <td><?php echo $stats['bowlingRuns']; ?></td>
                                        <td><?php echo $stats['bowlingBalls']; ?></td>
                                        <td><?php echo $stats['economy']; ?></td>
                                        <td><?php echo $stats['bestBowling']; ?></td>
                                    </tr>
                                <?php $rank++; endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Best Average -->
            <?php if (count($averageLeaderboard) > 0): ?>
            <div class="leaderboard-section">
                <div class="card">
                    <h2 class="leaderboard-title">
                        <span class="leaderboard-icon">📈</span>
                        Best Batting Average (Min 3 innings)
                    </h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Player</th>
                                    <th>Inn</th>
                                    <th>Runs</th>
                                    <th>Average</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $rank = 1;
                                foreach (array_slice($averageLeaderboard, 0, 10, true) as $stats):
                                ?>
                                    <tr>
                                        <td>
                                            <span class="rank-badge rank-<?php echo $rank <= 3 ? $rank : 'other'; ?>">
                                                <?php echo $rank; ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($stats['name']); ?></strong></td>
                                        <td><?php echo $stats['innings']; ?></td>
                                        <td><?php echo $stats['runs']; ?></td>
                                        <td><strong><?php echo $stats['average']; ?></strong></td>
                                    </tr>
                                <?php $rank++; endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Best Strike Rate -->
            <?php if (count($strikeRateLeaderboard) > 0): ?>
            <div class="leaderboard-section">
                <div class="card">
                    <h2 class="leaderboard-title">
                        <span class="leaderboard-icon">⚡</span>
                        Best Strike Rate (Min 30 balls)
                    </h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Player</th>
                                    <th>Balls</th>
                                    <th>Runs</th>
                                    <th>Strike Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $rank = 1;
                                foreach (array_slice($strikeRateLeaderboard, 0, 10, true) as $stats):
                                ?>
                                    <tr>
                                        <td>
                                            <span class="rank-badge rank-<?php echo $rank <= 3 ? $rank : 'other'; ?>">
                                                <?php echo $rank; ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($stats['name']); ?></strong></td>
                                        <td><?php echo $stats['balls']; ?></td>
                                        <td><?php echo $stats['runs']; ?></td>
                                        <td><strong><?php echo $stats['strikeRate']; ?></strong></td>
                                    </tr>
                                <?php $rank++; endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Best Economy -->
            <?php if (count($economyLeaderboard) > 0): ?>
            <div class="leaderboard-section">
                <div class="card">
                    <h2 class="leaderboard-title">
                        <span class="leaderboard-icon">🛡️</span>
                        Best Economy Rate (Min 4 overs)
                    </h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Player</th>
                                    <th>Overs</th>
                                    <th>Runs</th>
                                    <th>Economy</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $rank = 1;
                                foreach (array_slice($economyLeaderboard, 0, 10, true) as $stats):
                                    $overs = floor($stats['bowlingBalls'] / 6) . '.' . ($stats['bowlingBalls'] % 6);
                                ?>
                                    <tr>
                                        <td>
                                            <span class="rank-badge rank-<?php echo $rank <= 3 ? $rank : 'other'; ?>">
                                                <?php echo $rank; ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($stats['name']); ?></strong></td>
                                        <td><?php echo $overs; ?></td>
                                        <td><?php echo $stats['bowlingRuns']; ?></td>
                                        <td><strong><?php echo $stats['economy']; ?></strong></td>
                                    </tr>
                                <?php $rank++; endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="card">
                <p class="empty-state">No statistics available for this period. Try a different date range or verify more matches.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function exportStats() {
            const filter = new URLSearchParams(window.location.search).get('filter') || 'all';
            const start = new URLSearchParams(window.location.search).get('start') || '';
            const end = new URLSearchParams(window.location.search).get('end') || '';

            window.location.href = 'stats-export.php?filter=' + filter + '&start=' + start + '&end=' + end;
        }
    </script>
</body>
</html>
