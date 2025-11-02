<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#667eea">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="mobile-web-app-capable" content="yes">
  <title>Scheduled Match - StumpVision</title>
  <style>
    :root {
      --bg: #ffffff;
      --card: #f8fafc;
      --ink: #0f172a;
      --muted: #64748b;
      --line: #cbd5e1;
      --accent: #667eea;
      --accent-dark: #764ba2;
      --shadow: rgba(15, 23, 42, 0.1);
    }

    @media (prefers-color-scheme: dark) {
      :root {
        --bg: #0b1120;
        --card: #1e293b;
        --ink: #e2e8f0;
        --muted: #94a3b8;
        --line: #334155;
        --shadow: rgba(0, 0, 0, 0.3);
      }
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: system-ui, -apple-system, sans-serif;
      background: var(--bg);
      color: var(--ink);
      line-height: 1.5;
      -webkit-font-smoothing: antialiased;
      min-height: 100vh;
      padding: 20px;
    }

    .container {
      max-width: 800px;
      margin: 0 auto;
      animation: fadeIn 0.4s ease-out;
    }

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

    .header {
      text-align: center;
      margin-bottom: 30px;
    }

    .brand {
      font-size: 28px;
      font-weight: 700;
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 10px;
    }

    .badge {
      display: inline-block;
      padding: 6px 16px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      border-radius: 20px;
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 20px;
    }

    .card {
      background: var(--card);
      border-radius: 12px;
      padding: 24px;
      box-shadow: 0 2px 8px var(--shadow);
      margin-bottom: 20px;
    }

    .match-id-display {
      text-align: center;
      padding: 30px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      border-radius: 12px;
      margin-bottom: 20px;
    }

    .match-id-label {
      color: rgba(255, 255, 255, 0.9);
      font-size: 14px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 10px;
    }

    .match-id-value {
      font-size: 56px;
      font-weight: 700;
      color: white;
      font-family: 'Courier New', monospace;
      letter-spacing: 8px;
    }

    .datetime-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
      margin-bottom: 20px;
    }

    .datetime-card {
      background: var(--card);
      border: 2px solid var(--line);
      border-radius: 12px;
      padding: 20px;
      text-align: center;
    }

    .datetime-label {
      font-size: 12px;
      font-weight: 600;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 8px;
    }

    .datetime-value {
      font-size: 24px;
      font-weight: 700;
      color: var(--ink);
    }

    .section-title {
      font-size: 18px;
      font-weight: 700;
      margin-bottom: 15px;
      color: var(--ink);
    }

    .details-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-bottom: 20px;
    }

    .detail-item {
      background: var(--bg);
      border: 1px solid var(--line);
      border-radius: 8px;
      padding: 15px;
    }

    .detail-label {
      font-size: 12px;
      font-weight: 600;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 5px;
    }

    .detail-value {
      font-size: 20px;
      font-weight: 600;
      color: var(--ink);
    }

    .players-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 12px;
      margin-top: 15px;
    }

    .player-chip {
      background: var(--bg);
      border: 2px solid var(--line);
      border-radius: 8px;
      padding: 12px;
      display: flex;
      align-items: center;
      gap: 12px;
      transition: all 0.2s ease;
    }

    .player-chip:hover {
      border-color: var(--accent);
      transform: translateX(5px);
    }

    .player-number {
      width: 32px;
      height: 32px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 14px;
      flex-shrink: 0;
    }

    .player-info {
      flex: 1;
      min-width: 0;
    }

    .player-name {
      font-weight: 600;
      color: var(--ink);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .player-code {
      font-size: 12px;
      color: var(--muted);
      font-family: 'Courier New', monospace;
    }

    .buttons {
      display: flex;
      gap: 12px;
      margin-top: 30px;
    }

    .btn {
      flex: 1;
      padding: 16px 24px;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      text-decoration: none;
      display: inline-block;
      text-align: center;
    }

    .btn-primary {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .btn-secondary {
      background: var(--card);
      color: var(--ink);
      border: 2px solid var(--line);
    }

    .btn-secondary:hover {
      border-color: var(--accent);
      transform: translateY(-2px);
    }

    .loading {
      text-align: center;
      padding: 60px 20px;
    }

    .spinner {
      width: 40px;
      height: 40px;
      border: 4px solid var(--line);
      border-top-color: var(--accent);
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      margin: 0 auto 20px;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    .error {
      background: #fee2e2;
      color: #dc2626;
      padding: 20px;
      border-radius: 8px;
      text-align: center;
      margin-top: 20px;
    }

    @media (max-width: 640px) {
      .match-id-value {
        font-size: 36px;
        letter-spacing: 4px;
      }

      .datetime-grid {
        grid-template-columns: 1fr;
      }

      .buttons {
        flex-direction: column;
      }

      .players-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <div class="brand">StumpVision</div>
      <div class="badge">Scheduled Match</div>
    </div>

    <div id="content">
      <div class="loading">
        <div class="spinner"></div>
        <div style="color: var(--muted);">Loading match details...</div>
      </div>
    </div>
  </div>

  <script>
    // Get match ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const matchId = urlParams.get('match');

    if (!matchId) {
      showError('No match ID provided');
    } else {
      loadMatch(matchId);
    }

    async function loadMatch(matchId) {
      try {
        const response = await fetch(`api/scheduled-matches.php?action=get&id=${matchId}`);
        const data = await response.json();

        if (!data.ok) {
          throw new Error(data.err === 'not_found' ? 'Match not found' : 'Failed to load match');
        }

        renderMatch(data.match);
      } catch (error) {
        showError(error.message);
      }
    }

    function renderMatch(match) {
      const formatDate = (dateStr) => {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', {
          weekday: 'long',
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        });
      };

      const formatTime = (timeStr) => {
        if (!timeStr) return 'Not set';
        const [hours, minutes] = timeStr.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${minutes} ${ampm}`;
      };

      const html = `
        <div class="match-id-display">
          <div class="match-id-label">Match ID</div>
          <div class="match-id-value">${match.id}</div>
        </div>

        <div class="datetime-grid">
          <div class="datetime-card">
            <div class="datetime-label">📅 Date</div>
            <div class="datetime-value">${formatDate(match.scheduled_date)}</div>
          </div>
          <div class="datetime-card">
            <div class="datetime-label">🕐 Time</div>
            <div class="datetime-value">${formatTime(match.scheduled_time)}</div>
          </div>
        </div>

        ${match.match_name ? `
        <div class="card">
          <div class="section-title">${match.match_name}</div>
        </div>
        ` : ''}

        <div class="card">
          <div class="section-title">Match Details</div>
          <div class="details-grid">
            <div class="detail-item">
              <div class="detail-label">Format</div>
              <div class="detail-value">${match.matchFormat === 'limited' ? 'Limited Overs' : 'Test Match'}</div>
            </div>
            <div class="detail-item">
              <div class="detail-label">Overs</div>
              <div class="detail-value">${match.oversPerInnings}</div>
            </div>
            <div class="detail-item">
              <div class="detail-label">Wickets</div>
              <div class="detail-value">${match.wicketsLimit}</div>
            </div>
            <div class="detail-item">
              <div class="detail-label">Players</div>
              <div class="detail-value">${match.players.length}</div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="section-title">Players (${match.players.length})</div>
          <div class="players-grid">
            ${match.players.map((player, index) => `
              <div class="player-chip">
                <div class="player-number">${index + 1}</div>
                <div class="player-info">
                  <div class="player-name">${player.name}</div>
                  <div class="player-code">${player.code}</div>
                </div>
              </div>
            `).join('')}
          </div>
        </div>

        <div class="buttons">
          <a href="setup.php?match=${match.id}" class="btn btn-primary">
            Start Match Setup
          </a>
          <a href="index.php" class="btn btn-secondary">
            Back to Home
          </a>
        </div>
      `;

      document.getElementById('content').innerHTML = html;
    }

    function showError(message) {
      document.getElementById('content').innerHTML = `
        <div class="error">
          <strong>Error:</strong> ${message}
        </div>
        <div class="buttons">
          <a href="index.php" class="btn btn-secondary">
            Back to Home
          </a>
        </div>
      `;
    }
  </script>
</body>
</html>
