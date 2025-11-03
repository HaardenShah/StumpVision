<?php
declare(strict_types=1);
require_once 'auth.php';
require_once __DIR__ . '/../api/lib/Database.php';
require_once __DIR__ . '/../api/lib/repositories/PlayerRepository.php';

requireAdmin();
checkPasswordChangeRequired();

// Load players from database
function loadPlayers(): array {
    try {
        $repo = new \StumpVision\PlayerRepository();
        return $repo->findAll();
    } catch (\Exception $e) {
        error_log("Failed to load players: " . $e->getMessage());
        return [];
    }
}

$players = loadPlayers();
$csrfToken = getAdminCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Match - StumpVision Admin</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .schedule-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .player-selection {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
            max-height: 400px;
            overflow-y: auto;
            padding: 12px;
            border: 2px solid var(--border);
            border-radius: 8px;
            background: var(--bg-secondary);
            margin-bottom: 16px;
        }

        .player-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .player-checkbox:hover {
            background: var(--hover);
        }

        .player-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .player-checkbox label {
            cursor: pointer;
            flex: 1;
            font-size: 14px;
        }

        .selected-count {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .form-section {
            background: var(--bg-card);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 2px solid var(--border);
        }

        .form-section h3 {
            margin-bottom: 16px;
            color: var(--text);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: var(--text);
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
        }

        .match-id-display {
            background: var(--success-bg);
            color: var(--success);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin: 20px 0;
        }

        .match-id-display h2 {
            font-size: 48px;
            font-weight: 800;
            letter-spacing: 8px;
            font-family: monospace;
        }

        .match-id-display p {
            margin-top: 10px;
            font-size: 14px;
        }

        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .btn-group button {
            flex: 1;
        }

        .scheduled-matches-list {
            margin-top: 40px;
        }

        .match-card {
            background: var(--bg-card);
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
        }

        .match-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .match-id-badge {
            font-family: monospace;
            font-size: 24px;
            font-weight: 800;
            color: var(--primary);
        }

        .match-date {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .match-players {
            font-size: 14px;
            color: var(--text-secondary);
            margin-top: 8px;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .action-buttons button {
            padding: 6px 12px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="schedule-container">
        <div class="admin-header">
            <h1>Schedule Match</h1>
            <p>Pre-schedule matches for quick setup on match day</p>
        </div>

        <div id="successMessage" class="match-id-display" style="display: none;">
            <h2 id="generatedMatchId">------</h2>
            <p>Match ID - Share this with the scorer on match day</p>
        </div>

        <div id="scheduleForm">
            <form id="matchScheduleForm">
                <div class="form-section">
                    <h3>Match Details</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="matchName">Match Name (Optional)</label>
                            <input type="text" id="matchName" placeholder="e.g., Saturday League">
                        </div>
                        <div class="form-group">
                            <label for="scheduledDate">Scheduled Date</label>
                            <input type="date" id="scheduledDate" required>
                        </div>
                        <div class="form-group">
                            <label for="scheduledTime">Scheduled Time</label>
                            <input type="time" id="scheduledTime" value="10:00" required>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="matchFormat">Match Format</label>
                            <select id="matchFormat">
                                <option value="limited">Limited Overs</option>
                                <option value="test">Test Match</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="oversPerInnings">Overs per Innings</label>
                            <input type="number" id="oversPerInnings" value="20" min="1" max="50">
                        </div>
                        <div class="form-group">
                            <label for="wicketsLimit">Wickets Limit</label>
                            <input type="number" id="wicketsLimit" value="10" min="1" max="11">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Select Players</h3>
                    <div class="selected-count">
                        <span id="selectedCount">0</span> players selected
                    </div>
                    <div class="player-selection" id="playerSelection">
                        <?php if (empty($players)): ?>
                            <p style="grid-column: 1/-1; text-align: center; padding: 20px;">
                                No players registered yet. <a href="players.php">Register players first</a>.
                            </p>
                        <?php else: ?>
                            <?php foreach ($players as $player): ?>
                                <div class="player-checkbox">
                                    <input
                                        type="checkbox"
                                        id="player-<?= htmlspecialchars($player['id']) ?>"
                                        value="<?= htmlspecialchars($player['id']) ?>"
                                        data-name="<?= htmlspecialchars($player['name']) ?>"
                                        data-code="<?= htmlspecialchars($player['code']) ?>"
                                    >
                                    <label for="player-<?= htmlspecialchars($player['id']) ?>">
                                        <?= htmlspecialchars($player['name']) ?>
                                        <span style="font-family: monospace; font-size: 12px; color: var(--text-secondary);">
                                            (<?= htmlspecialchars($player['code']) ?>)
                                        </span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Schedule Match</button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">Reset</button>
                </div>
            </form>
        </div>

        <div class="scheduled-matches-list">
            <h2>Scheduled Matches</h2>
            <div id="matchesList">
                <p style="text-align: center; color: var(--text-secondary);">Loading...</p>
            </div>
        </div>
    </div>

    <script>
        // Update selected count
        document.getElementById('playerSelection').addEventListener('change', () => {
            const count = document.querySelectorAll('#playerSelection input:checked').length;
            document.getElementById('selectedCount').textContent = count;
        });

        // Set default date to today
        document.getElementById('scheduledDate').valueAsDate = new Date();

        // Handle form submission
        document.getElementById('matchScheduleForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const selectedPlayers = Array.from(document.querySelectorAll('#playerSelection input:checked'))
                .map(input => ({
                    id: input.value,
                    name: input.dataset.name,
                    code: input.dataset.code
                }));

            if (selectedPlayers.length < 2) {
                alert('Please select at least 2 players');
                return;
            }

            const payload = {
                match_name: document.getElementById('matchName').value,
                scheduled_date: document.getElementById('scheduledDate').value,
                scheduled_time: document.getElementById('scheduledTime').value,
                matchFormat: document.getElementById('matchFormat').value,
                oversPerInnings: parseInt(document.getElementById('oversPerInnings').value),
                wicketsLimit: parseInt(document.getElementById('wicketsLimit').value),
                players: selectedPlayers
            };

            try {
                const response = await fetch('/api/scheduled-matches.php?action=create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (result.ok && result.match) {
                    // Show success message with match ID
                    document.getElementById('generatedMatchId').textContent = result.match.id;
                    document.getElementById('successMessage').style.display = 'block';
                    document.getElementById('scheduleForm').style.display = 'none';

                    // Reload matches list
                    setTimeout(() => {
                        loadScheduledMatches();
                        resetForm();
                    }, 3000);
                } else {
                    alert('Failed to schedule match: ' + (result.err || 'Unknown error'));
                }
            } catch (err) {
                console.error('Error:', err);
                alert('Failed to schedule match');
            }
        });

        function resetForm() {
            document.getElementById('matchScheduleForm').reset();
            document.getElementById('scheduledDate').valueAsDate = new Date();
            document.getElementById('selectedCount').textContent = '0';
            document.getElementById('successMessage').style.display = 'none';
            document.getElementById('scheduleForm').style.display = 'block';
        }

        // Load scheduled matches
        async function loadScheduledMatches() {
            try {
                const response = await fetch('/api/scheduled-matches.php?action=list');
                const result = await response.json();

                if (result.ok && result.matches) {
                    const container = document.getElementById('matchesList');

                    if (result.matches.length === 0) {
                        container.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">No scheduled matches</p>';
                        return;
                    }

                    container.innerHTML = result.matches.map(match => {
                        const formatTime = (timeStr) => {
                            if (!timeStr) return '';
                            const [hours, minutes] = timeStr.split(':');
                            const hour = parseInt(hours);
                            const ampm = hour >= 12 ? 'PM' : 'AM';
                            const displayHour = hour % 12 || 12;
                            return `${displayHour}:${minutes} ${ampm}`;
                        };

                        return `
                        <div class="match-card">
                            <div class="match-card-header">
                                <div class="match-id-badge">${match.id}</div>
                                <div class="match-date">
                                    ${new Date(match.scheduled_date).toLocaleDateString()}
                                    ${match.scheduled_time ? ` at ${formatTime(match.scheduled_time)}` : ''}
                                </div>
                            </div>
                            <div>
                                ${match.match_name ? `<strong>${match.match_name}</strong><br>` : ''}
                                ${match.matchFormat === 'limited' ? `${match.oversPerInnings} overs` : 'Test Match'}
                                ${match.matchFormat === 'limited' ? `, ${match.wicketsLimit} wickets` : ''}
                            </div>
                            <div class="match-players">
                                ${match.players.length} players selected
                            </div>
                            <div class="action-buttons">
                                <button class="btn btn-secondary" onclick="viewMatch('${match.id}')">View</button>
                                <button class="btn btn-secondary" onclick="shareMatch('${match.id}')">Share Link</button>
                                <button class="btn btn-danger" onclick="deleteMatch('${match.id}')">Delete</button>
                            </div>
                        </div>
                        `;
                    }).join('');
                }
            } catch (err) {
                console.error('Error loading matches:', err);
            }
        }

        async function deleteMatch(matchId) {
            if (!confirm(`Delete match ${matchId}?`)) {
                return;
            }

            try {
                const response = await fetch('/api/scheduled-matches.php?action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: matchId })
                });

                const result = await response.json();

                if (result.ok) {
                    loadScheduledMatches();
                } else {
                    alert('Failed to delete match');
                }
            } catch (err) {
                console.error('Error:', err);
                alert('Failed to delete match');
            }
        }

        function shareMatch(matchId) {
            const baseUrl = window.location.origin;
            const shareUrl = `${baseUrl}/index.php?scheduled=${matchId}`;

            // Copy to clipboard
            navigator.clipboard.writeText(shareUrl).then(() => {
                alert(`Share link copied to clipboard!\n\nURL: ${shareUrl}\n\nShare this link with your group before the match.`);
            }).catch(() => {
                // Fallback if clipboard API fails
                prompt('Copy this link to share:', shareUrl);
            });
        }

        function viewMatch(matchId) {
            window.location.href = `scheduled.php?match=${matchId}`;
        }

        // Load matches on page load
        loadScheduledMatches();
    </script>
</body>
</html>
