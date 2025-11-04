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

// Load all verified matches from database
$verifiedMatches = $matchRepo->findAll(1000, 0, true);

foreach ($verifiedMatches as $matchData) {
    $matchDate = $matchData['created_at'] ?? 0;

    // Apply date filter
    if ($matchDate < $startDate || $matchDate > $endDate) {
        continue;
    }

    // Process innings
    $innings = $matchData['innings'] ?? [];
    foreach ($innings as $inning) {
        // Batting stats
        $batStats = $inning['batStats'] ?? [];
        foreach ($batStats as $stat) {
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
                    'notOuts' => 0
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
                    'notOuts' => 0
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

// Sort by runs
uasort($playerStats, fn($a, $b) => $b['runs'] <=> $a['runs']);

// Determine filename
$periodLabel = 'all-time';
switch ($filter) {
    case 'month':
        $periodLabel = date('Y-m');
        break;
    case 'year':
        $periodLabel = date('Y');
        break;
    case 'custom':
        if ($customStart && $customEnd) {
            $periodLabel = date('Y-m-d', strtotime($customStart)) . '_to_' . date('Y-m-d', strtotime($customEnd));
        }
        break;
}

$filename = 'stumpvision-stats-' . $periodLabel . '.csv';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// Write BOM for Excel UTF-8 support
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write headers
fputcsv($output, [
    'Player',
    'Matches',
    'Innings',
    'Runs',
    'Highest Score',
    'Average',
    'Strike Rate',
    'Hundreds',
    'Fifties',
    'Fours',
    'Sixes',
    'Ducks',
    'Wickets',
    'Bowling Runs',
    'Bowling Balls',
    'Economy',
    'Best Bowling'
]);

// Write data
foreach ($playerStats as $stats) {
    fputcsv($output, [
        $stats['name'],
        $stats['matches'],
        $stats['innings'],
        $stats['runs'],
        $stats['highest'],
        $stats['average'],
        $stats['strikeRate'],
        $stats['hundreds'],
        $stats['fifties'],
        $stats['fours'],
        $stats['sixes'],
        $stats['ducks'],
        $stats['wickets'],
        $stats['bowlingRuns'],
        $stats['bowlingBalls'],
        $stats['economy'],
        $stats['bestBowling']
    ]);
}

fclose($output);
exit;
