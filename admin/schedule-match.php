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
        $repo = new \StumpVision\Repositories\PlayerRepository();
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
        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        @keyframes shimmer {
            0% {
                background-position: -1000px 0;
            }
            100% {
                background-position: 1000px 0;
            }
        }

        .schedule-container {
            max-width: 900px;
            margin: 0 auto;
            animation: fadeIn 0.4s ease-out;
        }

        .admin-header {
            animation: fadeIn 0.3s ease-out;
        }

        .player-selection {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
            max-height: 400px;
            overflow-y: auto;
            padding: 12px;
            border: 2px solid var(--line);
            border-radius: 12px;
            background: var(--bg);
            margin-bottom: 16px;
        }

        .player-selection::-webkit-scrollbar {
            width: 8px;
        }

        .player-selection::-webkit-scrollbar-track {
            background: var(--bg);
            border-radius: 8px;
        }

        .player-selection::-webkit-scrollbar-thumb {
            background: var(--line);
            border-radius: 8px;
        }

        .player-selection::-webkit-scrollbar-thumb:hover {
            background: var(--accent);
        }

        .player-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            background: var(--card);
            border: 2px solid var(--line);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeIn 0.3s ease-out backwards;
        }

        .player-checkbox:nth-child(1) { animation-delay: 0.05s; }
        .player-checkbox:nth-child(2) { animation-delay: 0.1s; }
        .player-checkbox:nth-child(3) { animation-delay: 0.15s; }
        .player-checkbox:nth-child(4) { animation-delay: 0.2s; }

        .player-checkbox:hover {
            background: rgba(14, 165, 233, 0.1);
            border-color: var(--accent);
            transform: translateX(4px);
        }

        .player-checkbox:has(input:checked) {
            background: rgba(14, 165, 233, 0.15);
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }

        .player-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--accent);
            transition: transform 0.2s;
        }

        .player-checkbox input[type="checkbox"]:checked {
            transform: scale(1.1);
        }

        .player-checkbox label {
            cursor: pointer;
            flex: 1;
            font-size: 14px;
            font-weight: 500;
            color: var(--ink);
            transition: color 0.2s;
        }

        .player-checkbox:hover label {
            color: var(--accent);
        }

        .selected-count {
            font-size: 14px;
            font-weight: 600;
            color: var(--muted);
            margin-bottom: 12px;
            animation: fadeIn 0.3s ease-out;
        }

        .selected-count span {
            color: var(--accent);
            font-weight: 700;
            font-size: 16px;
        }

        .form-section {
            background: var(--card);
            padding: 24px;
            border-radius: 16px;
            margin-bottom: 24px;
            border: 2px solid var(--line);
            animation: fadeIn 0.4s ease-out backwards;
            transition: all 0.3s;
        }

        .form-section:nth-child(1) { animation-delay: 0.1s; }
        .form-section:nth-child(2) { animation-delay: 0.2s; }

        .form-section:hover {
            box-shadow: 0 4px 20px var(--shadow);
        }

        .form-section h3 {
            margin-bottom: 20px;
            color: var(--ink);
            font-weight: 700;
            font-size: 18px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--muted);
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid var(--line);
            border-radius: 8px;
            background: var(--bg);
            color: var(--ink);
            font-size: 14px;
            transition: all 0.25s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }

        .match-id-display {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2) 0%, rgba(34, 197, 94, 0.1) 100%);
            border: 2px solid var(--success);
            color: var(--success);
            padding: 32px;
            border-radius: 16px;
            text-align: center;
            margin: 24px 0;
            animation: scaleIn 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 32px rgba(34, 197, 94, 0.2);
        }

        .match-id-display h2 {
            font-size: 56px;
            font-weight: 900;
            letter-spacing: 12px;
            font-family: 'Courier New', monospace;
            margin-bottom: 12px;
            animation: pulse 2s ease-in-out infinite;
            text-shadow: 0 2px 8px rgba(34, 197, 94, 0.3);
        }

        .match-id-display p {
            margin-top: 12px;
            font-size: 15px;
            font-weight: 600;
            opacity: 0.9;
        }

        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            animation: fadeIn 0.5s ease-out backwards;
            animation-delay: 0.3s;
        }

        .btn-group button {
            flex: 1;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-group button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow);
        }

        .btn-group button:active {
            transform: translateY(0);
        }

        .btn {
            border: none;
            cursor: pointer;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-primary {
            background: var(--accent);
            color: white;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        .btn-secondary {
            background: var(--bg);
            border: 2px solid var(--line);
            color: var(--ink);
        }

        .btn-secondary:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            opacity: 0.9;
        }

        .scheduled-matches-list {
            margin-top: 48px;
            animation: fadeIn 0.6s ease-out backwards;
            animation-delay: 0.4s;
        }

        .scheduled-matches-list h2 {
            margin-bottom: 24px;
            font-size: 24px;
            font-weight: 800;
        }

        .match-card {
            background: var(--card);
            border: 2px solid var(--line);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeIn 0.4s ease-out backwards;
        }

        .match-card:nth-child(1) { animation-delay: 0.05s; }
        .match-card:nth-child(2) { animation-delay: 0.1s; }
        .match-card:nth-child(3) { animation-delay: 0.15s; }

        .match-card:hover {
            transform: translateX(8px);
            border-color: var(--accent);
            box-shadow: 0 8px 24px var(--shadow);
        }

        .match-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .match-id-badge {
            font-family: 'Courier New', monospace;
            font-size: 28px;
            font-weight: 900;
            color: var(--accent);
            letter-spacing: 2px;
            text-shadow: 0 2px 4px rgba(14, 165, 233, 0.2);
        }

        .match-date {
            font-size: 14px;
            font-weight: 600;
            color: var(--muted);
        }

        .match-players {
            font-size: 14px;
            font-weight: 500;
            color: var(--muted);
            margin-top: 12px;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            margin-top: 16px;
        }

        .action-buttons button {
            padding: 8px 14px;
            font-size: 13px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .action-buttons button:hover {
            transform: translateY(-2px);
        }

        /* Loading state */
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
            color: var(--muted);
        }

        .loading::after {
            content: '';
            width: 20px;
            height: 20px;
            border: 3px solid var(--line);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-left: 12px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .player-selection {
                grid-template-columns: 1fr;
                max-height: 300px;
            }

            .match-id-display h2 {
                font-size: 36px;
                letter-spacing: 6px;
            }

            .btn-group {
                flex-direction: column;
            }

            .match-card:hover {
                transform: translateY(-4px);
            }
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
                <div class="loading">Loading matches</div>
            </div>
        </div>
    </div>

    <script>
        // CSRF Token
        const csrfToken = <?= json_encode($csrfToken) ?>;

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
                csrf_token: csrfToken,
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
                    credentials: 'same-origin',
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
                const response = await fetch('/api/scheduled-matches.php?action=list', {
                    credentials: 'same-origin'
                });
                const result = await response.json();

                if (result.ok && result.matches) {
                    const container = document.getElementById('matchesList');

                    if (result.matches.length === 0) {
                        container.innerHTML = '<p class="empty-state">No scheduled matches yet</p>';
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
                    credentials: 'same-origin',
                    body: JSON.stringify({ csrf_token: csrfToken, id: matchId })
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
