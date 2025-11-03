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

// Load registered players
$registeredPlayers = $playerRepo->getAllAsAssociativeArray();

// Aggregate stats from verified matches
$playerStats = [];

// Load all verified matches from database
$verifiedMatches = $matchRepo->findAll(1000, 0, true);

foreach ($verifiedMatches as $matchData) {
    // Process innings
    $innings = $matchData['innings'] ?? [];
    foreach ($innings as $inning) {
        // Batting stats
        $batStats = $inning['batStats'] ?? [];
        foreach ($batStats as $stat) {
            $playerName = $stat['name'] ?? '';

            // Use player ID from match data if available (verified player)
            // Otherwise fall back to name-based slug for backward compatibility
            if (!empty($stat['playerId']) && isset($stat['verified']) && $stat['verified'] === true) {
                $playerId = $stat['playerId'];
            } else {
                // For guest players or old data, derive ID from name
                $playerId = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $playerName));
            }

            // Only count registered players
            if (!isset($registeredPlayers[$playerId])) {
                continue;
            }

            if (!isset($playerStats[$playerId])) {
                // Use the registered player's canonical name
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
                    'bestBowling' => '0/0'
                ];
            }

            $playerStats[$playerId]['matches']++;
            $playerStats[$playerId]['innings']++;
            $playerStats[$playerId]['runs'] += $stat['runs'] ?? 0;
            $playerStats[$playerId]['balls'] += $stat['balls'] ?? 0;
            $playerStats[$playerId]['fours'] += $stat['fours'] ?? 0;
            $playerStats[$playerId]['sixes'] += $stat['sixes'] ?? 0;

            if (($stat['runs'] ?? 0) > $playerStats[$playerId]['highest']) {
                $playerStats[$playerId]['highest'] = $stat['runs'] ?? 0;
            }

            if (($stat['runs'] ?? 0) >= 50 && ($stat['runs'] ?? 0) < 100) {
                $playerStats[$playerId]['fifties']++;
            } elseif (($stat['runs'] ?? 0) >= 100) {
                $playerStats[$playerId]['hundreds']++;
            }

            if (($stat['balls'] ?? 0) > 0 && ($stat['runs'] ?? 0) == 0 && ($stat['out'] ?? false)) {
                $playerStats[$playerId]['ducks']++;
            }
        }

        // Bowling stats
        $bowlStats = $inning['bowlStats'] ?? [];
        foreach ($bowlStats as $stat) {
            $playerName = $stat['name'] ?? '';

            // Use player ID from match data if available (verified player)
            // Otherwise fall back to name-based slug for backward compatibility
            if (!empty($stat['playerId']) && isset($stat['verified']) && $stat['verified'] === true) {
                $playerId = $stat['playerId'];
            } else {
                // For guest players or old data, derive ID from name
                $playerId = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $playerName));
            }

            if (!isset($registeredPlayers[$playerId])) {
                continue;
            }

            if (!isset($playerStats[$playerId])) {
                // Use the registered player's canonical name
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
                    'bestBowling' => '0/0'
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
    if ($stats['innings'] > 0) {
        $stats['average'] = round($stats['runs'] / $stats['innings'], 2);
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

// Sort by runs
uasort($playerStats, fn($a, $b) => $b['runs'] <=> $a['runs']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Statistics - StumpVision Admin</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <h1>Player Statistics</h1>

        <p style="margin-bottom: 20px; color: var(--muted);">
            Aggregate statistics from verified matches only. Only registered players are tracked.
        </p>

        <?php if (count($playerStats) > 0): ?>
            <div class="card">
                <h2>Batting Statistics</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
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
                            <?php foreach ($playerStats as $stats): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($stats['name']); ?></strong></td>
                                    <td><?php echo $stats['matches']; ?></td>
                                    <td><?php echo $stats['innings']; ?></td>
                                    <td><?php echo $stats['runs']; ?></td>
                                    <td><?php echo $stats['highest']; ?></td>
                                    <td><?php echo $stats['average']; ?></td>
                                    <td><?php echo $stats['strikeRate']; ?></td>
                                    <td><?php echo $stats['hundreds']; ?></td>
                                    <td><?php echo $stats['fifties']; ?></td>
                                    <td><?php echo $stats['fours']; ?></td>
                                    <td><?php echo $stats['sixes']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h2>Bowling Statistics</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
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
                            $bowlingStats = $playerStats;
                            uasort($bowlingStats, fn($a, $b) => $b['wickets'] <=> $a['wickets']);
                            foreach ($bowlingStats as $stats):
                                if ($stats['wickets'] == 0) continue;
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($stats['name']); ?></strong></td>
                                    <td><?php echo $stats['matches']; ?></td>
                                    <td><?php echo $stats['wickets']; ?></td>
                                    <td><?php echo $stats['bowlingRuns']; ?></td>
                                    <td><?php echo $stats['bowlingBalls']; ?></td>
                                    <td><?php echo $stats['economy']; ?></td>
                                    <td><?php echo $stats['bestBowling']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <p class="empty-state">No statistics available. Verify matches and register players to see stats.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
