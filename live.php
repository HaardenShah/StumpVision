<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#0f172a">
  <title>StumpVision - Live Score</title>
  <style>
    :root {
      --bg: #ffffff;
      --card: #f8fafc;
      --ink: #0f172a;
      --muted: #64748b;
      --line: #cbd5e1;
      --accent: #0ea5e9;
      --accent-light: #e0f2fe;
      --danger: #dc2626;
      --success: #16a34a;
      --shadow: rgba(15, 23, 42, 0.1);
    }
    @media (prefers-color-scheme: dark) {
      :root {
        --bg: #0b1120;
        --card: #1e293b;
        --ink: #e2e8f0;
        --muted: #94a3b8;
        --line: #334155;
        --accent: #0ea5e9;
        --accent-light: #164e63;
        --shadow: rgba(0, 0, 0, 0.3);
      }
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: system-ui, -apple-system, sans-serif; background: var(--bg); color: var(--ink); line-height: 1.5; padding: 20px; }
    .container { max-width: 800px; margin: 0 auto; }
    .header { text-align: center; margin-bottom: 32px; }
    .header h1 { font-size: 28px; font-weight: 800; margin-bottom: 8px; }
    .live-badge { background: var(--danger); color: white; padding: 4px 12px; border-radius: 999px; font-size: 13px; font-weight: 700; display: inline-block; animation: pulse 1.5s infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
    .score-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; padding: 32px; color: white; text-align: center; margin-bottom: 24px; box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3); }
    .score-main { font-size: 64px; font-weight: 900; margin-bottom: 12px; }
    .score-meta { font-size: 18px; opacity: 0.9; margin-bottom: 8px; }
    .team-name { font-size: 24px; font-weight: 700; margin-bottom: 20px; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .stat-card { background: var(--card); border: 2px solid var(--line); border-radius: 12px; padding: 16px; }
    .stat-label { font-size: 13px; color: var(--muted); font-weight: 600; text-transform: uppercase; margin-bottom: 4px; }
    .stat-value { font-size: 24px; font-weight: 800; color: var(--accent); }
    .batsman-card { background: var(--card); border: 2px solid var(--line); border-radius: 12px; padding: 16px; margin-bottom: 12px; }
    .batsman-name { font-weight: 700; font-size: 18px; margin-bottom: 8px; }
    .batsman-stats { color: var(--muted); font-size: 16px; }
    .striker-badge { background: var(--accent-light); color: var(--accent); padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 700; margin-left: 8px; }
    .error-msg { background: #fee2e2; color: #dc2626; padding: 16px; border-radius: 12px; text-align: center; }
    .loading { text-align: center; padding: 40px; color: var(--muted); }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>StumpVision</h1>
      <span class="live-badge" id="liveBadge">● LIVE</span>
    </div>

    <div id="errorContainer" style="display: none;"></div>
    <div id="loadingContainer" class="loading">
      <p>Loading live score...</p>
    </div>
    <div id="scoreContainer" style="display: none;"></div>
  </div>

  <script>
    const urlParams = new URLSearchParams(window.location.search);
    const liveId = urlParams.get('id');
    let lastUpdated = 0;
    let updateInterval;

    if (!liveId) {
      showError('No live match ID provided');
    } else {
      startLiveUpdates();
    }

    function showError(message) {
      document.getElementById('loadingContainer').style.display = 'none';
      document.getElementById('scoreContainer').style.display = 'none';
      const errorContainer = document.getElementById('errorContainer');
      errorContainer.innerHTML = `<div class="error-msg">${message}</div>`;
      errorContainer.style.display = 'block';
      document.getElementById('liveBadge').textContent = '● OFFLINE';
      document.getElementById('liveBadge').style.background = 'var(--muted)';
    }

    async function fetchLiveScore() {
      try {
        const response = await fetch(`api/live.php?action=get&live_id=${encodeURIComponent(liveId)}`);
        const result = await response.json();

        if (!result.ok) {
          if (result.err === 'live_score_disabled') {
            showError('Live score sharing is currently disabled on this server');
            clearInterval(updateInterval);
            return;
          }
          if (result.err === 'not_found' || result.err === 'session_inactive') {
            showError('Live match not found or has ended');
            clearInterval(updateInterval);
            return;
          }
          throw new Error(result.err || 'Failed to fetch live score');
        }

        if (result.state) {
          displayScore(result.state);
          lastUpdated = result.last_updated;
          document.getElementById('loadingContainer').style.display = 'none';
          document.getElementById('scoreContainer').style.display = 'block';
        }
      } catch (err) {
        console.error('Error fetching live score:', err);
        showError('Failed to load live score. Please try again.');
      }
    }

    function displayScore(state) {
      const container = document.getElementById('scoreContainer');

      const teamName = state.setup?.[state.battingTeam]?.name || 'Team';
      const score = `${state.score?.runs || 0}/${state.score?.wickets || 0}`;
      const overs = `${state.overs || 0}.${state.balls || 0}`;
      const runRate = state.overs > 0 ? ((state.score?.runs || 0) / (state.overs + state.balls / 6)).toFixed(2) : '0.00';

      let html = `
        <div class="score-card">
          <div class="team-name">${escapeHtml(teamName)}</div>
          <div class="score-main">${score}</div>
          <div class="score-meta">Overs: ${overs} | Run Rate: ${runRate}</div>
        </div>

        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-label">Innings</div>
            <div class="stat-value">${state.innings || 1}</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">This Over</div>
            <div class="stat-value">${state.thisOver?.join(' ') || '-'}</div>
          </div>
        </div>
      `;

      if (state.striker && state.batsmen) {
        const strikerStats = state.batsmen[state.striker];
        const nonStrikerStats = state.batsmen[state.nonStriker];

        html += `
          <h3 style="margin-bottom: 12px; font-size: 18px;">Current Batsmen</h3>
          <div class="batsman-card">
            <div class="batsman-name">
              ${escapeHtml(state.striker)}
              <span class="striker-badge">*</span>
            </div>
            <div class="batsman-stats">
              ${strikerStats?.runs || 0} (${strikerStats?.balls || 0})
              • ${strikerStats?.fours || 0}×4
              • ${strikerStats?.sixes || 0}×6
            </div>
          </div>
        `;

        if (state.nonStriker) {
          html += `
            <div class="batsman-card">
              <div class="batsman-name">${escapeHtml(state.nonStriker)}</div>
              <div class="batsman-stats">
                ${nonStrikerStats?.runs || 0} (${nonStrikerStats?.balls || 0})
                • ${nonStrikerStats?.fours || 0}×4
                • ${nonStrikerStats?.sixes || 0}×6
              </div>
            </div>
          `;
        }
      }

      container.innerHTML = html;
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    function startLiveUpdates() {
      // Initial fetch
      fetchLiveScore();

      // Poll every 3 seconds
      updateInterval = setInterval(fetchLiveScore, 3000);
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
      if (updateInterval) {
        clearInterval(updateInterval);
      }
    });
  </script>
</body>
</html>
