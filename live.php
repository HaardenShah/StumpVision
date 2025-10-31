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
      --warning: #f59e0b;
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
    .container { max-width: 1200px; margin: 0 auto; }
    .header { text-align: center; margin-bottom: 32px; }
    .header h1 { font-size: 28px; font-weight: 800; margin-bottom: 8px; }
    .live-badge { background: var(--danger); color: white; padding: 4px 12px; border-radius: 999px; font-size: 13px; font-weight: 700; display: inline-block; animation: pulse 1.5s infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }

    /* Score Card */
    .score-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; padding: 32px; color: white; text-align: center; margin-bottom: 24px; box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3); }
    .score-main { font-size: 64px; font-weight: 900; margin-bottom: 12px; }
    .score-meta { font-size: 18px; opacity: 0.9; margin-bottom: 8px; }
    .team-name { font-size: 24px; font-weight: 700; margin-bottom: 20px; }
    .target-info { font-size: 16px; opacity: 0.95; margin-top: 12px; font-weight: 600; }

    /* Stats Grid */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .stat-card { background: var(--card); border: 2px solid var(--line); border-radius: 12px; padding: 16px; text-align: center; }
    .stat-label { font-size: 12px; color: var(--muted); font-weight: 600; text-transform: uppercase; margin-bottom: 4px; }
    .stat-value { font-size: 24px; font-weight: 800; color: var(--accent); }
    .stat-sub { font-size: 13px; color: var(--muted); margin-top: 4px; }

    /* Section Headers */
    .section-header { font-size: 20px; font-weight: 700; margin-bottom: 16px; margin-top: 32px; padding-bottom: 8px; border-bottom: 3px solid var(--accent); }

    /* Player Cards */
    .player-card { background: var(--card); border: 2px solid var(--line); border-radius: 12px; padding: 16px; margin-bottom: 12px; }
    .player-name { font-weight: 700; font-size: 18px; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; }
    .player-stats { color: var(--muted); font-size: 16px; }
    .striker-badge { background: var(--accent-light); color: var(--accent); padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 700; }
    .bowling-badge { background: var(--warning); color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 700; }
    .out-badge { background: var(--danger); color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 700; }

    /* Table */
    .stats-table { width: 100%; background: var(--card); border: 2px solid var(--line); border-radius: 12px; overflow: hidden; margin-bottom: 24px; }
    .stats-table table { width: 100%; border-collapse: collapse; }
    .stats-table th { background: var(--line); padding: 12px; text-align: left; font-size: 13px; font-weight: 700; text-transform: uppercase; color: var(--ink); }
    .stats-table td { padding: 12px; border-top: 1px solid var(--line); font-size: 15px; }
    .stats-table tr:hover { background: var(--line); }
    .stats-table .highlight { background: var(--accent-light); font-weight: 700; }

    /* Recent Balls */
    .recent-balls { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 24px; }
    .ball { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-weight: 700; border-radius: 8px; font-size: 14px; }
    .ball-runs { background: var(--card); border: 2px solid var(--line); color: var(--ink); }
    .ball-wicket { background: var(--danger); color: white; }
    .ball-extra { background: var(--warning); color: white; }

    /* Partnership */
    .partnership-card { background: var(--card); border: 2px solid var(--accent); border-radius: 12px; padding: 20px; margin-bottom: 24px; }
    .partnership-title { font-size: 16px; font-weight: 700; color: var(--accent); margin-bottom: 12px; }
    .partnership-stats { font-size: 28px; font-weight: 800; color: var(--ink); margin-bottom: 8px; }
    .partnership-meta { color: var(--muted); font-size: 14px; }

    /* Fall of Wickets */
    .fow-list { display: flex; flex-wrap: wrap; gap: 12px; }
    .fow-item { background: var(--card); border: 2px solid var(--line); border-radius: 8px; padding: 12px; min-width: 140px; }
    .fow-score { font-size: 18px; font-weight: 700; color: var(--danger); }
    .fow-player { font-size: 13px; color: var(--muted); margin-top: 4px; }

    /* Projection */
    .projection-card { background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); border-radius: 12px; padding: 24px; color: white; text-align: center; }
    .projection-value { font-size: 48px; font-weight: 900; }
    .projection-label { font-size: 14px; opacity: 0.9; margin-top: 8px; }

    .error-msg { background: #fee2e2; color: #dc2626; padding: 16px; border-radius: 12px; text-align: center; }
    .loading { text-align: center; padding: 40px; color: var(--muted); }

    @media (max-width: 768px) {
      .score-main { font-size: 48px; }
      .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
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
      const bowlingTeamName = state.setup?.[state.bowlingTeam]?.name || 'Team';
      const score = `${state.score?.runs || 0}/${state.score?.wickets || 0}`;
      const overs = `${state.overs || 0}.${state.balls || 0}`;
      const totalBalls = (state.overs || 0) * 6 + (state.balls || 0);
      const currentRunRate = totalBalls > 0 ? ((state.score?.runs || 0) / (totalBalls / 6)).toFixed(2) : '0.00';

      // Calculate projection
      const maxOvers = state.setup?.oversPerInnings || 20;
      const maxBalls = maxOvers * 6;
      const projection = totalBalls > 0 ? Math.round((state.score?.runs || 0) * maxBalls / totalBalls) : 0;

      // Target and required run rate (if innings 2)
      let targetInfo = '';
      let requiredRunRate = null;
      if (state.innings === 2 && state.firstInningsScore) {
        const target = state.firstInningsScore + 1;
        const needed = target - (state.score?.runs || 0);
        const ballsRemaining = maxBalls - totalBalls;
        const oversRemaining = (ballsRemaining / 6).toFixed(1);
        requiredRunRate = ballsRemaining > 0 ? (needed / (ballsRemaining / 6)).toFixed(2) : '0.00';

        if (needed > 0) {
          targetInfo = `<div class="target-info">Target: ${target} | Need ${needed} runs from ${ballsRemaining} balls (${oversRemaining} overs) | RRR: ${requiredRunRate}</div>`;
        } else {
          targetInfo = `<div class="target-info" style="color: #4ade80;">${teamName} won by ${state.setup.wicketsLimit - state.score.wickets} wickets!</div>`;
        }
      } else if (state.innings === 1) {
        targetInfo = `<div class="target-info">First Innings</div>`;
      }

      let html = `
        <div class="score-card">
          <div class="team-name">${escapeHtml(teamName)}</div>
          <div class="score-main">${score}</div>
          <div class="score-meta">Overs: ${overs} / ${maxOvers} | CRR: ${currentRunRate}</div>
          ${targetInfo}
        </div>

        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-label">Innings</div>
            <div class="stat-value">${state.innings || 1}</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Run Rate</div>
            <div class="stat-value">${currentRunRate}</div>
            <div class="stat-sub">Current</div>
          </div>
          ${state.innings === 2 && requiredRunRate ? `
          <div class="stat-card">
            <div class="stat-label">Required RR</div>
            <div class="stat-value">${requiredRunRate}</div>
            <div class="stat-sub">To Win</div>
          </div>
          ` : `
          <div class="stat-card">
            <div class="stat-label">Projection</div>
            <div class="stat-value">${projection}</div>
            <div class="stat-sub">Est. Final Score</div>
          </div>
          `}
          <div class="stat-card">
            <div class="stat-label">This Over</div>
            <div class="stat-value">${state.thisOver?.join(' ') || '-'}</div>
          </div>
        </div>
      `;

      // Current Partnership
      if (state.currentPartnership && state.currentPartnership.runs > 0) {
        const p = state.currentPartnership;
        html += `
          <div class="partnership-card">
            <div class="partnership-title">Current Partnership</div>
            <div class="partnership-stats">${p.runs} runs</div>
            <div class="partnership-meta">${p.balls} balls • ${p.batsman1} & ${p.batsman2}</div>
          </div>
        `;
      }

      // Current Batsmen
      if (state.striker && state.batsmen) {
        html += `<h3 class="section-header">Current Batsmen</h3>`;

        const strikerStats = state.batsmen[state.striker];
        const strikerSR = strikerStats?.balls > 0 ? ((strikerStats.runs / strikerStats.balls) * 100).toFixed(1) : '0.0';
        html += `
          <div class="player-card">
            <div class="player-name">
              <span>${escapeHtml(state.striker)}</span>
              <span class="striker-badge">STRIKER</span>
            </div>
            <div class="player-stats">
              <strong>${strikerStats?.runs || 0}</strong> (${strikerStats?.balls || 0})
              • ${strikerStats?.fours || 0}×4
              • ${strikerStats?.sixes || 0}×6
              • SR: ${strikerSR}
            </div>
          </div>
        `;

        if (state.nonStriker) {
          const nonStrikerStats = state.batsmen[state.nonStriker];
          const nonStrikerSR = nonStrikerStats?.balls > 0 ? ((nonStrikerStats.runs / nonStrikerStats.balls) * 100).toFixed(1) : '0.0';
          html += `
            <div class="player-card">
              <div class="player-name">
                <span>${escapeHtml(state.nonStriker)}</span>
              </div>
              <div class="player-stats">
                <strong>${nonStrikerStats?.runs || 0}</strong> (${nonStrikerStats?.balls || 0})
                • ${nonStrikerStats?.fours || 0}×4
                • ${nonStrikerStats?.sixes || 0}×6
                • SR: ${nonStrikerSR}
              </div>
            </div>
          `;
        }
      }

      // Current Bowler
      if (state.bowler && state.bowlers) {
        html += `<h3 class="section-header">Current Bowler</h3>`;
        const bowlerStats = state.bowlers[state.bowler];
        const bowlerOvers = Math.floor(bowlerStats.balls / 6);
        const bowlerBalls = bowlerStats.balls % 6;
        const bowlerEcon = bowlerStats.balls > 0 ? (bowlerStats.runs / (bowlerStats.balls / 6)).toFixed(2) : '0.00';

        html += `
          <div class="player-card">
            <div class="player-name">
              <span>${escapeHtml(state.bowler)}</span>
              <span class="bowling-badge">BOWLING</span>
            </div>
            <div class="player-stats">
              <strong>${bowlerStats?.wickets || 0}/${bowlerStats?.runs || 0}</strong>
              • ${bowlerOvers}.${bowlerBalls} overs
              • Econ: ${bowlerEcon}
              ${bowlerStats?.dots ? `• Dots: ${bowlerStats.dots}` : ''}
            </div>
          </div>
        `;
      }

      // Recent Balls
      if (state.ballHistory && state.ballHistory.length > 0) {
        html += `<h3 class="section-header">Recent Balls</h3>`;
        html += `<div class="recent-balls">`;
        const recentBalls = state.ballHistory.slice(-20).reverse();
        recentBalls.forEach(ball => {
          let ballClass = 'ball-runs';
          let ballText = '';

          if (ball.type === 'wicket') {
            ballClass = 'ball-wicket';
            ballText = 'W';
          } else if (ball.type === 'noball' || ball.type === 'wide') {
            ballClass = 'ball-extra';
            ballText = ball.type === 'noball' ? `${ball.runs}+NB` : `${ball.runs}WD`;
          } else if (ball.type === 'bye') {
            ballClass = 'ball-extra';
            ballText = `${ball.runs}B`;
          } else if (ball.type === 'legbye') {
            ballClass = 'ball-extra';
            ballText = `${ball.runs}LB`;
          } else {
            ballText = ball.runs === 0 ? '•' : ball.runs;
          }

          html += `<div class="ball ${ballClass}">${ballText}</div>`;
        });
        html += `</div>`;
      }

      // Batting Scorecard - Only show batsmen from batting team who have batted
      const battingTeamPlayers = state.setup?.[state.battingTeam]?.players || [];
      const battersWhoHaveBatted = Object.entries(state.batsmen || {}).filter(([name, stats]) => {
        return battingTeamPlayers.includes(name) && (stats.balls > 0 || stats.out);
      });

      if (battersWhoHaveBatted.length > 0) {
        html += `<h3 class="section-header">Batting Scorecard - ${escapeHtml(teamName)}</h3>`;
        html += `<div class="stats-table"><table>`;
        html += `
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
        `;

        battersWhoHaveBatted.forEach(([name, stats]) => {
          const sr = stats.balls > 0 ? ((stats.runs / stats.balls) * 100).toFixed(1) : '0.0';
          const isActive = name === state.striker || name === state.nonStriker;
          const rowClass = isActive ? 'highlight' : '';
          const status = stats.out ? `<span class="out-badge">OUT</span>` : isActive ? `<span class="striker-badge">*</span>` : '';

          html += `
            <tr class="${rowClass}">
              <td>${escapeHtml(name)} ${status}</td>
              <td><strong>${stats.runs}</strong></td>
              <td>${stats.balls}</td>
              <td>${stats.fours}</td>
              <td>${stats.sixes}</td>
              <td>${sr}</td>
            </tr>
          `;
        });

        html += `</tbody></table></div>`;
      }

      // Remaining Batsmen - Show batsmen who haven't batted yet
      const remainingBatsmen = battingTeamPlayers.filter(name => {
        const stats = state.batsmen?.[name];
        return !stats || (stats.balls === 0 && !stats.out);
      });

      if (remainingBatsmen.length > 0) {
        html += `<h3 class="section-header">Remaining Batsmen</h3>`;
        html += `<div class="player-card">`;
        html += `<div class="player-stats">`;
        html += remainingBatsmen.map(name => escapeHtml(name)).join(' • ');
        html += `</div></div>`;
      }

      // Bowling Scorecard - Only show bowlers from bowling team who have bowled
      const bowlingTeamPlayers = state.setup?.[state.bowlingTeam]?.players || [];
      const bowlersWhoHaveBowled = Object.entries(state.bowlers || {}).filter(([name, stats]) => {
        return bowlingTeamPlayers.includes(name) && stats.balls > 0;
      });

      if (bowlersWhoHaveBowled.length > 0) {
        html += `<h3 class="section-header">Bowling Scorecard - ${escapeHtml(bowlingTeamName)}</h3>`;
        html += `<div class="stats-table"><table>`;
        html += `
          <thead>
            <tr>
              <th>Bowler</th>
              <th>O</th>
              <th>R</th>
              <th>W</th>
              <th>Econ</th>
              <th>Dots</th>
            </tr>
          </thead>
          <tbody>
        `;

        bowlersWhoHaveBowled.forEach(([name, stats]) => {
          const overs = Math.floor(stats.balls / 6);
          const balls = stats.balls % 6;
          const econ = stats.balls > 0 ? (stats.runs / (stats.balls / 6)).toFixed(2) : '0.00';
          const isActive = name === state.bowler;
          const rowClass = isActive ? 'highlight' : '';
          const status = isActive ? `<span class="bowling-badge">BOWLING</span>` : '';

          html += `
            <tr class="${rowClass}">
              <td>${escapeHtml(name)} ${status}</td>
              <td>${overs}.${balls}</td>
              <td>${stats.runs}</td>
              <td><strong>${stats.wickets}</strong></td>
              <td>${econ}</td>
              <td>${stats.dots || 0}</td>
            </tr>
          `;
        });

        html += `</tbody></table></div>`;
      }

      // Remaining Bowlers - Show bowlers who haven't bowled yet
      const remainingBowlers = bowlingTeamPlayers.filter(name => {
        const stats = state.bowlers?.[name];
        return !stats || stats.balls === 0;
      });

      if (remainingBowlers.length > 0) {
        html += `<h3 class="section-header">Remaining Bowlers</h3>`;
        html += `<div class="player-card">`;
        html += `<div class="player-stats">`;
        html += remainingBowlers.map(name => escapeHtml(name)).join(' • ');
        html += `</div></div>`;
      }

      // Extras
      if (state.extras) {
        const totalExtras = (state.extras.nb || 0) + (state.extras.wd || 0) + (state.extras.b || 0) + (state.extras.lb || 0);
        if (totalExtras > 0) {
          html += `<h3 class="section-header">Extras</h3>`;
          html += `<div class="player-card">`;
          html += `<div class="player-stats">`;
          html += `<strong>Total: ${totalExtras}</strong> • `;
          html += `No-balls: ${state.extras.nb || 0} • `;
          html += `Wides: ${state.extras.wd || 0} • `;
          html += `Byes: ${state.extras.b || 0} • `;
          html += `Leg-byes: ${state.extras.lb || 0}`;
          html += `</div></div>`;
        }
      }

      // Fall of Wickets
      if (state.wicketsFallen && state.wicketsFallen.length > 0) {
        html += `<h3 class="section-header">Fall of Wickets</h3>`;
        html += `<div class="fow-list">`;
        state.wicketsFallen.forEach((wicket, idx) => {
          html += `
            <div class="fow-item">
              <div class="fow-score">${wicket.score}-${idx + 1}</div>
              <div class="fow-player">${escapeHtml(wicket.batsman)}</div>
              <div class="fow-player" style="font-size: 11px;">${wicket.overs}.${wicket.balls} ov</div>
            </div>
          `;
        });
        html += `</div>`;
      }

      // Past Partnerships
      if (state.partnerships && state.partnerships.length > 0) {
        html += `<h3 class="section-header">Partnerships</h3>`;
        html += `<div class="stats-table"><table>`;
        html += `
          <thead>
            <tr>
              <th>Wicket</th>
              <th>Runs</th>
              <th>Balls</th>
              <th>Batsmen</th>
            </tr>
          </thead>
          <tbody>
        `;

        state.partnerships.forEach((p, idx) => {
          html += `
            <tr>
              <td>${idx + 1}${idx === 0 ? 'st' : idx === 1 ? 'nd' : idx === 2 ? 'rd' : 'th'}</td>
              <td><strong>${p.runs}</strong></td>
              <td>${p.balls}</td>
              <td>${escapeHtml(p.batsman1)} & ${escapeHtml(p.batsman2)}</td>
            </tr>
          `;
        });

        html += `</tbody></table></div>`;
      }

      // Milestones
      if (state.milestones && state.milestones.length > 0) {
        html += `<h3 class="section-header">Milestones</h3>`;
        html += `<div class="player-card">`;
        html += `<div class="player-stats">`;
        state.milestones.forEach((m, idx) => {
          if (idx > 0) html += ' • ';
          html += `${escapeHtml(m.player)}: ${m.milestone} (${m.balls}b)`;
        });
        html += `</div></div>`;
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
