<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#0f172a">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="mobile-web-app-capable" content="yes">
  <link rel="manifest" href="manifest.webmanifest">
  <link rel="apple-touch-icon" href="assets/icons/icon-192.png">
  <title>StumpVision - Live Match</title>
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
      --danger-light: #fee2e2;
      --success: #16a34a;
      --success-light: #dcfce7;
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
        --danger: #ef4444;
        --danger-light: #7f1d1d;
        --success: #22c55e;
        --success-light: #14532d;
        --shadow: rgba(0, 0, 0, 0.3);
      }
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { 
      font-family: system-ui, -apple-system, sans-serif; 
      background: var(--bg); 
      color: var(--ink); 
      line-height: 1.5; 
      -webkit-font-smoothing: antialiased;
      overflow-x: hidden;
      position: fixed;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
    }
    .header { position: sticky; top: 0; z-index: 100; background: var(--card); border-bottom: 2px solid var(--line); box-shadow: 0 2px 8px var(--shadow); }
    .header-top { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; }
    .brand { font-size: 20px; font-weight: 800; }
    .settings-btn { background: var(--card); border: 2px solid var(--line); color: var(--ink); padding: 8px 16px; border-radius: 999px; font-weight: 600; cursor: pointer; }
    .score-display { padding: 20px 16px; text-align: center; background: linear-gradient(135deg, var(--accent-light), var(--card)); }
    .score-main { font-size: 48px; font-weight: 900; margin-bottom: 8px; }
    .score-meta { display: flex; justify-content: center; gap: 20px; color: var(--muted); font-size: 14px; font-weight: 600; }
    .free-hit-badge { background: var(--danger); color: white; padding: 6px 12px; border-radius: 999px; font-size: 13px; font-weight: 700; display: inline-block; margin-top: 8px; animation: pulse 1.5s infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
    .tabs { display: flex; background: var(--card); border-bottom: 2px solid var(--line); }
    .tab { flex: 1; padding: 14px 16px; border: none; background: none; color: var(--muted); font-weight: 600; cursor: pointer; border-bottom: 3px solid transparent; }
    .tab.active { color: var(--accent); border-bottom-color: var(--accent); }
    .tab-content { display: none; padding: 16px; padding-bottom: 180px; overflow-y: auto; max-height: calc(100vh - 280px); }
    .tab-content.active { display: block; }
    .tab-content.no-dock { padding-bottom: 16px; }
    .current-players { background: var(--card); border: 2px solid var(--line); border-radius: 16px; padding: 16px; margin-bottom: 16px; }
    .player-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--line); }
    .player-row:last-child { border-bottom: none; }
    .player-name { font-weight: 700; }
    .player-stats { color: var(--muted); font-size: 14px; }
    .striker-badge { background: var(--accent-light); color: var(--accent); padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 700; margin-left: 8px; }
    .retired-badge { background: var(--muted); color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 700; margin-left: 8px; }
    .scoring-dock { position: fixed; bottom: 0; left: 0; right: 0; background: var(--card); border-top: 2px solid var(--line); padding: 12px; box-shadow: 0 -4px 20px var(--shadow); z-index: 50; }
    .scoring-dock.hidden { display: none; }
    .pad-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px; max-width: 800px; margin: 0 auto; }
    @media (max-width: 640px) { .pad-grid { grid-template-columns: repeat(4, 1fr); } }
    .pad-btn { background: var(--card); border: 2px solid var(--line); color: var(--ink); padding: 16px 8px; border-radius: 12px; font-size: 20px; font-weight: 800; cursor: pointer; min-height: 56px; }
    .pad-btn:active { transform: scale(0.95); }
    .pad-btn.boundary { background: var(--success-light); border-color: var(--success); color: var(--success); }
    .pad-btn.wicket { background: var(--danger-light); border-color: var(--danger); color: var(--danger); }
    .pad-btn.extra { background: var(--accent-light); border-color: var(--accent); color: var(--accent); }
    .stats-table { background: var(--card); border: 2px solid var(--line); border-radius: 16px; overflow: hidden; margin-bottom: 16px; }
    .stats-title { padding: 12px 16px; background: var(--accent-light); font-weight: 700; color: var(--accent); }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 10px 12px; text-align: left; font-size: 14px; }
    th { font-weight: 700; color: var(--muted); font-size: 12px; text-transform: uppercase; border-bottom: 2px solid var(--line); }
    td { border-bottom: 1px solid var(--line); }
    tr:last-child td { border-bottom: none; }
    .modal { position: fixed; inset: 0; background: rgba(0, 0, 0, 0.7); display: none; align-items: center; justify-content: center; z-index: 200; padding: 20px; }
    .modal.active { display: flex; }
    .modal-content { background: var(--card); border: 2px solid var(--line); border-radius: 20px; padding: 24px; max-width: 400px; width: 100%; max-height: 80vh; overflow-y: auto; }
    .modal-title { font-size: 18px; font-weight: 700; margin-bottom: 16px; color: #ffffff; }
    .modal-buttons { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 16px; }
    .modal-btn { padding: 14px; background: var(--card); border: 2px solid var(--line); border-radius: 12px; font-weight: 800; font-size: 18px; color: #ffffff; cursor: pointer; }
    .modal-btn.active { background: var(--accent-light); border-color: var(--accent); color: var(--accent); }
    .btn-cancel { background: var(--danger-light); border: 2px solid var(--danger); color: var(--danger); padding: 12px; border-radius: 12px; font-weight: 600; cursor: pointer; width: 100%; }
    select.modal-select { width: 100%; padding: 12px; border: 2px solid var(--line); border-radius: 12px; background: var(--bg); color: var(--ink); font-size: 15px; margin-bottom: 16px; }
    .btn-primary { width: 100%; padding: 14px; background: var(--accent); border: none; border-radius: 12px; color: white; font-weight: 700; cursor: pointer; margin-bottom: 8px; }
    .btn-secondary { width: 100%; padding: 12px; background: var(--card); border: 2px solid var(--line); color: var(--ink); border-radius: 12px; font-weight: 600; cursor: pointer; margin-bottom: 8px; }
    .over-display { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; }
    .ball-badge { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: var(--card); border: 2px solid var(--line); border-radius: 8px; font-weight: 700; font-size: 14px; }
    .ball-badge.boundary { background: var(--success-light); border-color: var(--success); color: var(--success); }
    .ball-badge.wicket { background: var(--danger-light); border-color: var(--danger); color: var(--danger); }
    .settings-panel { padding: 16px; }
    .settings-item { background: var(--card); border: 2px solid var(--line); border-radius: 12px; padding: 16px; margin-bottom: 12px; }
    .settings-item h3 { font-size: 16px; margin-bottom: 12px; }
    .settings-item p { color: var(--muted); font-size: 14px; margin-bottom: 8px; }
    .hint { color: var(--muted); font-size: 13px; }
    .input-row { display: flex; gap: 8px; align-items: center; margin-bottom: 12px; }
    .input-row label { flex: 1; font-weight: 600; }
    .input-row input { flex: 1; padding: 10px; border: 2px solid var(--line); border-radius: 8px; background: var(--bg); color: var(--ink); font-size: 15px; }
    .player-list { margin-top: 12px; }
    .player-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; background: var(--bg); border-radius: 8px; margin-bottom: 8px; }
    .player-item-name { font-weight: 600; }
    .player-item-actions { display: flex; gap: 8px; }
    .player-item-btn { padding: 6px 12px; border: 2px solid var(--line); border-radius: 6px; background: var(--card); color: var(--ink); font-size: 12px; font-weight: 600; cursor: pointer; }
    .player-item-btn.danger { border-color: var(--danger); color: var(--danger); }
    .toast-container { position: fixed; top: 80px; right: 20px; z-index: 1000; display: flex; flex-direction: column; gap: 10px; max-width: 350px; }
    @media (max-width: 640px) { .toast-container { right: 10px; left: 10px; max-width: none; } }
    .toast { background: var(--card); border: 2px solid var(--line); border-radius: 12px; padding: 16px; box-shadow: 0 4px 12px var(--shadow); display: flex; align-items: center; gap: 12px; animation: slideIn 0.3s ease; }
    .toast.success { border-left: 4px solid var(--success); }
    .toast.error { border-left: 4px solid var(--danger); }
    .toast.info { border-left: 4px solid var(--accent); }
    .toast-message { flex: 1; font-size: 14px; font-weight: 500; }
    .toast-close { background: none; border: none; color: var(--muted); cursor: pointer; font-size: 20px; font-weight: bold; padding: 0 4px; }
    @keyframes slideIn { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
  </style>
</head>
<body>
  <div class="toast-container" id="toastContainer" aria-live="polite" aria-atomic="true"></div>
  <div class="header">
    <div class="header-top">
      <div class="brand">StumpVision</div>
      <button class="settings-btn" onclick="showTab('settings')">Settings</button>
    </div>
    <div class="score-display">
      <div class="score-main" id="mainScore" aria-live="polite" aria-atomic="true">0/0</div>
      <div class="score-meta">
        <span id="teamName">Team A</span>
        <span>Overs: <strong id="oversDisplay" aria-live="polite">0.0</strong></span>
        <span>RR: <strong id="runRate" aria-live="polite">0.00</strong></span>
      </div>
      <div id="targetDisplay" style="display: none; margin-top: 8px; font-size: 16px; font-weight: 600;" aria-live="polite"></div>
      <div id="freeHitBadge" class="free-hit-badge" style="display: none;" role="status" aria-live="assertive">FREE HIT</div>
    </div>
    <div class="tabs">
      <button class="tab active" onclick="showTab('score')">Score</button>
      <button class="tab" onclick="showTab('stats')">Stats</button>
      <button class="tab" onclick="showTab('settings')">Settings</button>
    </div>
  </div>

  <div id="scoreTab" class="tab-content active">
    <div class="current-players">
      <div class="player-row">
        <div><span class="player-name" id="striker">Striker</span><span class="striker-badge">*</span></div>
        <div class="player-stats" id="strikerStats">0(0)</div>
      </div>
      <div class="player-row">
        <div><span class="player-name" id="nonStriker">Non-Striker</span></div>
        <div class="player-stats" id="nonStrikerStats">0(0)</div>
      </div>
      <div class="player-row">
        <div><span class="player-name" id="bowler">Bowler</span></div>
        <div class="player-stats" id="bowlerStats">0-0 (0.0)</div>
      </div>
    </div>
    <div class="current-players">
      <div style="font-weight: 700; margin-bottom: 8px;">This Over</div>
      <div class="over-display" id="thisOver"></div>
    </div>
    <div class="current-players">
      <div style="font-weight: 700; margin-bottom: 8px;">Quick Actions</div>
      <button class="btn-primary" onclick="swapStrike()">Swap Strike</button>
      <button class="btn-primary" onclick="retireBatsman()">Retire Batsman</button>
      <button class="btn-primary" onclick="undoLastBall()">Undo Last Ball</button>
    </div>
  </div>

  <div id="statsTab" class="tab-content no-dock">
    <div class="stats-table">
      <div class="stats-title">Match Summary</div>
      <table>
        <tbody>
          <tr><td><strong>Current Score</strong></td><td id="statCurrentScore">0/0</td></tr>
          <tr><td><strong>Run Rate</strong></td><td id="statRunRate">0.00</td></tr>
          <tr><td><strong>Projected Score</strong></td><td id="statProjected">0</td></tr>
          <tr><td><strong>Overs Remaining</strong></td><td id="statOversRemaining">0.0</td></tr>
        </tbody>
      </table>
    </div>

    <div class="stats-table">
      <div class="stats-title">Batting Stats</div>
      <table>
        <thead><tr><th>Player</th><th>R</th><th>B</th><th>4s</th><th>6s</th><th>SR</th><th>Status</th></tr></thead>
        <tbody id="battingStats"></tbody>
      </table>
    </div>

    <div class="stats-table">
      <div class="stats-title">Partnerships</div>
      <table>
        <thead><tr><th>Wicket</th><th>Runs</th><th>Balls</th><th>Partners</th></tr></thead>
        <tbody id="partnershipStats"></tbody>
      </table>
    </div>

    <div class="stats-table">
      <div class="stats-title">Bowling Stats</div>
      <table>
        <thead><tr><th>Player</th><th>O</th><th>M</th><th>R</th><th>W</th><th>Econ</th><th>Dots</th></tr></thead>
        <tbody id="bowlingStats"></tbody>
      </table>
    </div>

    <div class="stats-table">
      <div class="stats-title">Extras Breakdown</div>
      <table>
        <tbody>
          <tr><td>No Balls</td><td id="nbCount">0</td></tr>
          <tr><td>Wides</td><td id="wdCount">0</td></tr>
          <tr><td>Byes</td><td id="bCount">0</td></tr>
          <tr><td>Leg Byes</td><td id="lbCount">0</td></tr>
          <tr><td><strong>Total Extras</strong></td><td id="totalExtras"><strong>0</strong></td></tr>
        </tbody>
      </table>
    </div>

    <div class="stats-table">
      <div class="stats-title">Scoring Rate by Overs</div>
      <table>
        <thead><tr><th>Phase</th><th>Overs</th><th>Runs</th><th>RR</th><th>Wkts</th></tr></thead>
        <tbody id="overPhaseStats"></tbody>
      </table>
    </div>

    <div class="stats-table">
      <div class="stats-title">Key Milestones</div>
      <table>
        <tbody id="milestoneStats">
          <tr><td colspan="2" style="text-align: center; color: var(--muted);">No milestones yet</td></tr>
        </tbody>
      </table>
    </div>

    <div class="stats-table">
      <div class="stats-title">Fall of Wickets</div>
      <table>
        <tbody id="wicketFall">
          <tr><td colspan="2" style="text-align: center; color: var(--muted);">No wickets fallen</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div id="settingsTab" class="tab-content no-dock">
    <div class="settings-panel">
      <div class="settings-item">
        <h3>Match Info</h3>
        <p><strong>Format:</strong> <span id="settingsFormat">Limited Overs</span></p>
        <div class="input-row">
          <label>Overs per innings:</label>
          <input type="number" id="oversInput" min="1" max="50" value="20">
          <button class="player-item-btn" onclick="updateOvers()">Update</button>
        </div>
        <div class="input-row">
          <label>Wickets limit:</label>
          <input type="number" id="wicketsInput" min="1" max="11" value="10">
          <button class="player-item-btn" onclick="updateWickets()">Update</button>
        </div>
      </div>
      <div class="settings-item">
        <h3>Teams</h3>
        <p><strong>Team A:</strong> <span id="settingsTeamA">Team A</span></p>
        <p><strong>Team B:</strong> <span id="settingsTeamB">Team B</span></p>
      </div>
      <div class="settings-item">
        <h3>Player Management</h3>
        <button class="btn-primary" onclick="showPlayerManagement()">Manage Players</button>
        <p class="hint">Add, remove, or bring back retired players</p>
      </div>
      <div class="settings-item">
        <h3>Match Actions</h3>
        <button class="btn-primary" onclick="saveMatch()">Save Match</button>
        <button class="btn-primary" onclick="shareRecap()">Share Score Card</button>
        <p class="hint" id="saveStatus"></p>
      </div>
      <div class="settings-item" id="liveShareSection" style="display: none;">
        <h3>Live Score Sharing</h3>
        <p style="margin-bottom: 12px; font-size: 14px;">Share a link so others can watch this match live</p>
        <button class="btn-primary" id="startLiveBtn" onclick="startLiveSharing()">Start Live Sharing</button>
        <button class="btn-primary" id="stopLiveBtn" onclick="stopLiveSharing()" style="display: none; background: var(--danger);">Stop Live Sharing</button>
        <div id="liveShareLink" style="display: none; margin-top: 12px; padding: 12px; background: var(--accent-light); border-radius: 8px;">
          <p style="font-size: 13px; font-weight: 600; margin-bottom: 6px;">Live Score Link:</p>
          <input type="text" id="liveShareUrl" readonly style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--line); font-size: 13px; font-family: monospace;">
          <button class="btn-secondary" onclick="copyLiveLink()" style="margin-top: 8px;">Copy Link</button>
        </div>
        <p class="hint">Note: Live sharing is currently disabled on this server</p>
      </div>
      <button class="btn-primary" onclick="newInnings()">Start New Innings</button>
      <button class="btn-primary" style="background: var(--danger);" onclick="resetMatch()">Reset Match</button>
    </div>
  </div>

  <div class="scoring-dock" id="scoringDock" role="toolbar" aria-label="Cricket scoring controls">
    <div class="pad-grid">
      <button class="pad-btn" onclick="recordBall(0)" aria-label="Record 0 runs - dot ball" title="Dot ball">0</button>
      <button class="pad-btn" onclick="recordBall(1)" aria-label="Record 1 run" title="1 run">1</button>
      <button class="pad-btn" onclick="recordBall(2)" aria-label="Record 2 runs" title="2 runs">2</button>
      <button class="pad-btn" onclick="recordBall(3)" aria-label="Record 3 runs" title="3 runs">3</button>
      <button class="pad-btn boundary" onclick="recordBall(4)" aria-label="Record 4 runs - boundary" title="4 runs (boundary)">4</button>
      <button class="pad-btn boundary" onclick="recordBall(6)" aria-label="Record 6 runs - six" title="6 runs (six)">6</button>
      <button class="pad-btn extra" onclick="showNoBallModal()" aria-label="Record no ball" title="No Ball">NB</button>
      <button class="pad-btn extra" onclick="showWideModal()" aria-label="Record wide ball" title="Wide">WD</button>
      <button class="pad-btn extra" onclick="showByeModal()" aria-label="Record byes" title="Byes">B</button>
      <button class="pad-btn extra" onclick="showLegByeModal()" aria-label="Record leg byes" title="Leg Byes">LB</button>
      <button class="pad-btn wicket" onclick="showWicketModal()" aria-label="Record wicket" title="Wicket">W</button>
      <button class="pad-btn wicket" onclick="undoLastBall()" aria-label="Undo last ball" title="Undo">Undo</button>
    </div>
  </div>

  <!-- Modals -->
  <div id="noBallModal" class="modal">
    <div class="modal-content">
      <div class="modal-title">No Ball - Runs off bat?</div>
      <div class="modal-buttons">
        <button class="modal-btn" onclick="processNoBall(0)">0</button>
        <button class="modal-btn" onclick="processNoBall(1)">1</button>
        <button class="modal-btn" onclick="processNoBall(2)">2</button>
        <button class="modal-btn" onclick="processNoBall(3)">3</button>
        <button class="modal-btn" onclick="processNoBall(4)">4</button>
        <button class="modal-btn" onclick="processNoBall(6)">6</button>
      </div>
      <button class="btn-cancel" onclick="closeModal('noBallModal')">Cancel</button>
    </div>
  </div>

  <div id="wideModal" class="modal">
    <div class="modal-content">
      <div class="modal-title">Wide Ball</div>
      <p style="color: #ff6b6b; font-size: 14px; margin-bottom: 8px; font-weight: 700;">⚠️ If batsman hit it, use regular run buttons!</p>
      <p style="color: #ffffff; font-size: 15px; margin-bottom: 12px; font-weight: 600;">Total runs (1 penalty + any runs/overthrows):</p>
      <div class="modal-buttons">
        <button class="modal-btn" onclick="processWide(1)">1</button>
        <button class="modal-btn" onclick="processWide(2)">2</button>
        <button class="modal-btn" onclick="processWide(3)">3</button>
        <button class="modal-btn" onclick="processWide(4)">4</button>
        <button class="modal-btn" onclick="processWide(5)">5</button>
      </div>
      <p style="color: #ffffff; font-size: 13px; margin-top: 8px; font-weight: 600;">Examples: Just wide = 1, Wide + 1 run = 2, Wide to boundary = 5</p>
      <button class="btn-cancel" onclick="closeModal('wideModal')">Cancel</button>
    </div>
  </div>

  <div id="byeModal" class="modal">
    <div class="modal-content">
      <div class="modal-title">Byes - How many?</div>
      <div class="modal-buttons">
        <button class="modal-btn" onclick="processBye(1)">1</button>
        <button class="modal-btn" onclick="processBye(2)">2</button>
        <button class="modal-btn" onclick="processBye(3)">3</button>
        <button class="modal-btn" onclick="processBye(4)">4</button>
      </div>
      <button class="btn-cancel" onclick="closeModal('byeModal')">Cancel</button>
    </div>
  </div>

  <div id="legByeModal" class="modal">
    <div class="modal-content">
      <div class="modal-title">Leg Byes - How many?</div>
      <div class="modal-buttons">
        <button class="modal-btn" onclick="processLegBye(1)">1</button>
        <button class="modal-btn" onclick="processLegBye(2)">2</button>
        <button class="modal-btn" onclick="processLegBye(3)">3</button>
        <button class="modal-btn" onclick="processLegBye(4)">4</button>
      </div>
      <button class="btn-cancel" onclick="closeModal('legByeModal')">Cancel</button>
    </div>
  </div>

  <div id="wicketModal" class="modal">
    <div class="modal-content">
      <div class="modal-title" id="wicketModalTitle">Wicket Type</div>
      <div id="wicketTypeButtons" class="modal-buttons" style="grid-template-columns: repeat(2, 1fr);">
        <button class="modal-btn" onclick="selectWicketType('bowled')">Bowled</button>
        <button class="modal-btn" onclick="selectWicketType('caught')">Caught</button>
        <button class="modal-btn" onclick="selectWicketType('lbw')">LBW</button>
        <button class="modal-btn" onclick="selectWicketType('stumped')">Stumped</button>
        <button class="modal-btn" onclick="showRunOutModal()">Run Out</button>
        <button class="modal-btn" onclick="selectWicketType('hitwicket')">Hit Wicket</button>
      </div>
      <button class="btn-cancel" onclick="closeModal('wicketModal')">Cancel</button>
    </div>
  </div>

  <div id="runOutModal" class="modal">
    <div class="modal-content">
      <div class="modal-title">Run Out - Runs completed?</div>
      <div class="modal-buttons">
        <button class="modal-btn" onclick="processRunOut(0)">0</button>
        <button class="modal-btn" onclick="processRunOut(1)">1</button>
        <button class="modal-btn" onclick="processRunOut(2)">2</button>
        <button class="modal-btn" onclick="processRunOut(3)">3</button>
      </div>
      <div style="margin: 16px 0;">
        <div style="font-weight: 700; font-size: 15px; margin-bottom: 8px; color: #ffffff;">Who got run out?</div>
        <select id="runOutBatsmanSelect" class="modal-select">
          <option value="striker">Striker</option>
          <option value="nonStriker">Non-Striker</option>
        </select>
      </div>
      <button class="btn-cancel" onclick="closeModal('runOutModal')">Cancel</button>
    </div>
  </div>

  <div id="newOverModal" class="modal">
    <div class="modal-content">
      <div class="modal-title">Select New Bowler</div>
      <select id="newBowlerSelect" class="modal-select"></select>
      <button class="btn-primary" onclick="confirmNewOver()">Continue</button>
    </div>
  </div>

  <div id="batsmanModal" class="modal">
    <div class="modal-content">
      <div class="modal-title" id="batsmanModalTitle">New Batsman</div>
      <select id="newBatsmanSelect" class="modal-select"></select>
      <button class="btn-primary" onclick="confirmNewBatsman()">Continue</button>
    </div>
  </div>

  <div id="playerManagementModal" class="modal">
    <div class="modal-content">
      <div class="modal-title">Player Management</div>
      
      <h3 style="margin-top: 16px; margin-bottom: 8px; font-size: 15px;">Batting Team (<span id="pmBattingTeamName"></span>)</h3>
      <div id="battingTeamPlayers" class="player-list"></div>
      <div class="input-row" style="margin-top: 12px;">
        <input type="text" id="newBattingPlayer" placeholder="New player name...">
        <button class="player-item-btn" onclick="addNewPlayer('batting')">Add</button>
      </div>
      
      <h3 style="margin-top: 16px; margin-bottom: 8px; font-size: 15px;">Bowling Team (<span id="pmBowlingTeamName"></span>)</h3>
      <div id="bowlingTeamPlayers" class="player-list"></div>
      <div class="input-row" style="margin-top: 12px;">
        <input type="text" id="newBowlingPlayer" placeholder="New player name...">
        <button class="player-item-btn" onclick="addNewPlayer('bowling')">Add</button>
      </div>
      
      <button class="btn-primary" style="margin-top: 16px;" onclick="closeModal('playerManagementModal')">Done</button>
    </div>
  </div>

  <script>
    console.log('Script loading...');
    
    function haptic(style) {
      try {
        if ('vibrate' in navigator) {
          const patterns = {
            light: 10,
            medium: 20,
            heavy: 40,
            success: [10, 30, 10],
            error: [20, 50, 20]
          };
          navigator.vibrate(patterns[style] || patterns.light);
        }
      } catch (e) {
        console.log('Haptics not available');
      }
    }

    // Toast notification system
    function showToast(message, type = 'info', duration = 3000) {
      const container = document.getElementById('toastContainer');
      const toast = document.createElement('div');
      toast.className = `toast ${type}`;
      toast.setAttribute('role', 'alert');

      const messageSpan = document.createElement('span');
      messageSpan.className = 'toast-message';
      messageSpan.textContent = message;

      const closeBtn = document.createElement('button');
      closeBtn.className = 'toast-close';
      closeBtn.textContent = '×';
      closeBtn.setAttribute('aria-label', 'Close notification');
      closeBtn.addEventListener('click', () => {
        toast.remove();
      });

      toast.appendChild(messageSpan);
      toast.appendChild(closeBtn);
      container.appendChild(toast);

      // Auto remove after duration
      setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(400px)';
        setTimeout(() => toast.remove(), 300);
      }, duration);
    }

    function getBallEmoji() {
      const isDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
      return isDark ? '\u26AA' : '\u26AB';
    }

    let matchState = {
      setup: null,
      innings: 1,
      battingTeam: 'teamA',
      bowlingTeam: 'teamB',
      score: { runs: 0, wickets: 0 },
      overs: 0,
      balls: 0,
      striker: null,
      nonStriker: null,
      bowler: null,
      freeHit: false,
      thisOver: [],
      batsmen: {},
      bowlers: {},
      extras: { nb: 0, wd: 0, b: 0, lb: 0 },
      ballHistory: [],
      firstInningsScore: null,
      firstInningsData: null, // Will store complete first innings stats
      saveId: null,
      pendingOverComplete: false,
      runOutVictim: null,
      partnerships: [],
      currentPartnership: { runs: 0, balls: 0, batsman1: null, batsman2: null },
      wicketsFallen: [],
      milestones: [],
      matchCompleted: false // Flag to indicate match is finished
    };

    function addToBallHistory(entry) {
      matchState.ballHistory.push(entry);
      if (matchState.ballHistory.length > 50) {
        matchState.ballHistory.shift();
      }
    }

    async function loadScheduledMatch(matchId) {
      try {
        const response = await fetch(`api/scheduled-matches.php?action=get&id=${matchId}`);
        const data = await response.json();

        if (!data.ok) {
          throw new Error(data.err === 'not_found' ? 'Match not found' : 'Failed to load scheduled match');
        }

        showScheduledMatchView(data.match);
      } catch (error) {
        console.error('Error loading scheduled match:', error);
        showScheduledMatchError(error.message);
      }
    }

    function showScheduledMatchView(match) {
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

      // Replace entire body content with scheduled match view
      document.body.innerHTML = `
        <style>
          .scheduled-view {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            font-family: system-ui, -apple-system, sans-serif;
          }
          .scheduled-header {
            text-align: center;
            margin-bottom: 30px;
          }
          .scheduled-brand {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
          }
          .scheduled-badge {
            display: inline-block;
            padding: 6px 16px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
          }
          .scheduled-match-id {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 12px;
            margin-bottom: 20px;
          }
          .scheduled-match-id-label {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
          }
          .scheduled-match-id-value {
            font-size: 56px;
            font-weight: 700;
            color: white;
            font-family: 'Courier New', monospace;
            letter-spacing: 8px;
          }
          .scheduled-datetime {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
          }
          .scheduled-datetime-card {
            background: var(--card);
            border: 2px solid var(--line);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
          }
          .scheduled-datetime-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
          }
          .scheduled-datetime-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--ink);
          }
          .scheduled-card {
            background: var(--card);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px var(--shadow);
            margin-bottom: 20px;
          }
          .scheduled-section-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--ink);
          }
          .scheduled-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
          }
          .scheduled-detail-item {
            background: var(--bg);
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 15px;
          }
          .scheduled-detail-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
          }
          .scheduled-detail-value {
            font-size: 20px;
            font-weight: 600;
            color: var(--ink);
          }
          .scheduled-players-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 12px;
            margin-top: 15px;
          }
          .scheduled-player-chip {
            background: var(--bg);
            border: 2px solid var(--line);
            border-radius: 8px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
          }
          .scheduled-player-number {
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
          .scheduled-player-info {
            flex: 1;
            min-width: 0;
          }
          .scheduled-player-name {
            font-weight: 600;
            color: var(--ink);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
          }
          .scheduled-player-code {
            font-size: 12px;
            color: var(--muted);
            font-family: 'Courier New', monospace;
          }
          .scheduled-buttons {
            display: flex;
            gap: 12px;
            margin-top: 30px;
          }
          .scheduled-btn {
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
          .scheduled-btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
          }
          .scheduled-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
          }
          .scheduled-btn-secondary {
            background: var(--card);
            color: var(--ink);
            border: 2px solid var(--line);
          }
          .scheduled-btn-secondary:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
          }
          @media (max-width: 640px) {
            .scheduled-match-id-value {
              font-size: 36px;
              letter-spacing: 4px;
            }
            .scheduled-datetime {
              grid-template-columns: 1fr;
            }
            .scheduled-buttons {
              flex-direction: column;
            }
            .scheduled-players-grid {
              grid-template-columns: 1fr;
            }
          }
        </style>
        <div class="scheduled-view">
          <div class="scheduled-header">
            <div class="scheduled-brand">StumpVision</div>
            <div class="scheduled-badge">Scheduled Match</div>
          </div>

          <div class="scheduled-match-id">
            <div class="scheduled-match-id-label">Match ID</div>
            <div class="scheduled-match-id-value">${match.id}</div>
          </div>

          <div class="scheduled-datetime">
            <div class="scheduled-datetime-card">
              <div class="scheduled-datetime-label">📅 Date</div>
              <div class="scheduled-datetime-value">${formatDate(match.scheduled_date)}</div>
            </div>
            <div class="scheduled-datetime-card">
              <div class="scheduled-datetime-label">🕐 Time</div>
              <div class="scheduled-datetime-value">${formatTime(match.scheduled_time)}</div>
            </div>
          </div>

          ${match.match_name ? `
          <div class="scheduled-card">
            <div class="scheduled-section-title">${match.match_name}</div>
          </div>
          ` : ''}

          <div class="scheduled-card">
            <div class="scheduled-section-title">Match Details</div>
            <div class="scheduled-details-grid">
              <div class="scheduled-detail-item">
                <div class="scheduled-detail-label">Format</div>
                <div class="scheduled-detail-value">${match.matchFormat === 'limited' ? 'Limited Overs' : 'Test Match'}</div>
              </div>
              <div class="scheduled-detail-item">
                <div class="scheduled-detail-label">Overs</div>
                <div class="scheduled-detail-value">${match.oversPerInnings}</div>
              </div>
              <div class="scheduled-detail-item">
                <div class="scheduled-detail-label">Wickets</div>
                <div class="scheduled-detail-value">${match.wicketsLimit}</div>
              </div>
              <div class="scheduled-detail-item">
                <div class="scheduled-detail-label">Players</div>
                <div class="scheduled-detail-value">${match.players.length}</div>
              </div>
            </div>
          </div>

          <div class="scheduled-card">
            <div class="scheduled-section-title">Players (${match.players.length})</div>
            <div class="scheduled-players-grid">
              ${match.players.map((player, index) => `
                <div class="scheduled-player-chip">
                  <div class="scheduled-player-number">${index + 1}</div>
                  <div class="scheduled-player-info">
                    <div class="scheduled-player-name">${player.name}</div>
                    <div class="scheduled-player-code">${player.code}</div>
                  </div>
                </div>
              `).join('')}
            </div>
          </div>

          <div class="scheduled-buttons">
            <a href="setup.php?match=${match.id}" class="scheduled-btn scheduled-btn-primary">
              Start Match Setup
            </a>
            <a href="index.php" class="scheduled-btn scheduled-btn-secondary">
              Back to Home
            </a>
          </div>
        </div>
      `;
    }

    function showScheduledMatchError(message) {
      document.body.innerHTML = `
        <div style="max-width: 600px; margin: 100px auto; padding: 20px; text-align: center; font-family: system-ui;">
          <div style="font-size: 48px; margin-bottom: 20px;">⚠️</div>
          <h2 style="color: var(--ink); margin-bottom: 10px;">Error Loading Match</h2>
          <p style="color: var(--muted); margin-bottom: 30px;">${message}</p>
          <a href="index.php" style="display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">
            Back to Home
          </a>
        </div>
      `;
    }

    async function init() {
      console.log('Initializing...');

      // Check if loading a scheduled match from URL
      const urlParams = new URLSearchParams(window.location.search);
      const scheduledId = urlParams.get('scheduled');

      if (scheduledId) {
        console.log('Loading scheduled match:', scheduledId);
        await loadScheduledMatch(scheduledId);
        return;
      }

      const saved = localStorage.getItem('stumpvision_match');
      if (!saved) {
        console.log('No match data, redirecting to setup');
        window.location.href = 'setup.php';
        return;
      }

      matchState.setup = JSON.parse(saved);
      console.log('Match setup loaded:', matchState.setup);

      if (matchState.setup.tossDecision === 'bat') {
        matchState.battingTeam = matchState.setup.tossWinner;
        matchState.bowlingTeam = matchState.setup.tossWinner === 'teamA' ? 'teamB' : 'teamA';
      } else {
        matchState.bowlingTeam = matchState.setup.tossWinner;
        matchState.battingTeam = matchState.setup.tossWinner === 'teamA' ? 'teamB' : 'teamA';
      }

      // Handle both old format (array of strings) and new format (array of objects)
      matchState.setup.teamA.players.forEach(p => {
        const playerName = typeof p === 'string' ? p : p.name;
        matchState.batsmen[playerName] = { runs: 0, balls: 0, fours: 0, sixes: 0, out: false, outType: null, retired: false };
        matchState.bowlers[playerName] = { overs: 0, balls: 0, runs: 0, wickets: 0, maidens: 0, dots: 0 };
      });
      matchState.setup.teamB.players.forEach(p => {
        const playerName = typeof p === 'string' ? p : p.name;
        matchState.batsmen[playerName] = { runs: 0, balls: 0, fours: 0, sixes: 0, out: false, outType: null, retired: false };
        matchState.bowlers[playerName] = { overs: 0, balls: 0, runs: 0, wickets: 0, maidens: 0, dots: 0 };
      });

      promptStartingPlayers();
      updateDisplay();
      updateSettings();
      console.log('Initialization complete');
    }

    function promptStartingPlayers() {
      if (matchState.setup.openingBat1 && matchState.setup.openingBat2 && matchState.setup.openingBowler) {
        matchState.striker = matchState.setup.openingBat1;
        matchState.nonStriker = matchState.setup.openingBat2;
        matchState.bowler = matchState.setup.openingBowler;
      } else {
        const battingPlayers = matchState.setup[matchState.battingTeam].players;
        const bowlingPlayers = matchState.setup[matchState.bowlingTeam].players;

        // Handle both old format (strings) and new format (objects)
        matchState.striker = typeof battingPlayers[0] === 'string' ? battingPlayers[0] : battingPlayers[0].name;
        matchState.nonStriker = typeof battingPlayers[1] === 'string' ? battingPlayers[1] : battingPlayers[1].name;
        matchState.bowler = typeof bowlingPlayers[0] === 'string' ? bowlingPlayers[0] : bowlingPlayers[0].name;
      }

      // Initialize first partnership
      matchState.currentPartnership = {
        runs: 0,
        balls: 0,
        batsman1: matchState.striker,
        batsman2: matchState.nonStriker
      };

      console.log('Starting players:', { striker: matchState.striker, nonStriker: matchState.nonStriker, bowler: matchState.bowler });
    }

    function recordBall(runs) {
      console.log('Recording ball with runs:', runs);
      if (!matchState.striker || !matchState.bowler) {
        console.error('Missing striker or bowler');
        return;
      }

      haptic(runs === 4 || runs === 6 ? 'success' : 'light');

      // Track dot ball
      if (runs === 0) {
        matchState.bowlers[matchState.bowler].dots += 1;
      }

      // Update batsman stats
      const previousRuns = matchState.batsmen[matchState.striker].runs;
      matchState.batsmen[matchState.striker].runs += runs;
      matchState.batsmen[matchState.striker].balls += 1;
      if (runs === 4) matchState.batsmen[matchState.striker].fours += 1;
      if (runs === 6) matchState.batsmen[matchState.striker].sixes += 1;

      // Check for milestones (50, 100)
      checkMilestone(matchState.striker, previousRuns, matchState.batsmen[matchState.striker].runs);

      matchState.bowlers[matchState.bowler].runs += runs;
      matchState.bowlers[matchState.bowler].balls += 1;

      matchState.score.runs += runs;
      matchState.balls += 1;

      // Update partnership
      matchState.currentPartnership.runs += runs;
      matchState.currentPartnership.balls += 1;

      if (runs % 2 === 1) {
        [matchState.striker, matchState.nonStriker] = [matchState.nonStriker, matchState.striker];
      }

      matchState.thisOver.push(runs === 0 ? getBallEmoji() : runs.toString());

      if (matchState.balls === 6) {
        completeOver();
      }

      matchState.freeHit = false;

      addToBallHistory({
        type: 'legal',
        runs: runs,
        striker: matchState.striker,
        nonStriker: matchState.nonStriker,
        bowler: matchState.bowler
      });

      console.log('Match state after ball:', matchState);
      updateDisplay();

      // Auto-save: On first ball, save to generate match ID immediately
      // Then continue saving periodically to keep match data updated
      autoSaveMatch();
    }

    function showNoBallModal() {
      document.getElementById('noBallModal').classList.add('active');
    }

    function processNoBall(batRuns) {
      closeModal('noBallModal');
      haptic('medium');

      matchState.batsmen[matchState.striker].runs += batRuns;
      matchState.batsmen[matchState.striker].balls += 1;
      if (batRuns === 4) matchState.batsmen[matchState.striker].fours += 1;
      if (batRuns === 6) matchState.batsmen[matchState.striker].sixes += 1;

      // No ball counts against bowler's stats (but not as a legal ball towards the over)
      matchState.bowlers[matchState.bowler].runs += (1 + batRuns);
      // Note: We don't increment matchState.balls (over balls) but we do count it for bowler stats
      matchState.score.runs += (1 + batRuns);
      matchState.extras.nb += 1;

      matchState.thisOver.push(`${batRuns}+NB`);
      matchState.freeHit = true;

      if (batRuns % 2 === 1) {
        [matchState.striker, matchState.nonStriker] = [matchState.nonStriker, matchState.striker];
      }

      addToBallHistory({ type: 'noball', runs: batRuns, striker: matchState.striker, bowler: matchState.bowler });
      updateDisplay();
      autoSaveMatch();
    }

    function showWideModal() {
      document.getElementById('wideModal').classList.add('active');
    }

    function processWide(totalRuns) {
      closeModal('wideModal');
      haptic('light');

      matchState.bowlers[matchState.bowler].runs += totalRuns;
      matchState.score.runs += totalRuns;
      matchState.extras.wd += 1;  // Always count as 1 wide in extras
      matchState.thisOver.push(`${totalRuns}WD`);

      addToBallHistory({ type: 'wide', runs: totalRuns, bowler: matchState.bowler });
      updateDisplay();
      autoSaveMatch();
    }

    function showByeModal() {
      document.getElementById('byeModal').classList.add('active');
    }

    function processBye(runs) {
      closeModal('byeModal');

      matchState.batsmen[matchState.striker].balls += 1;
      matchState.bowlers[matchState.bowler].balls += 1;
      matchState.score.runs += runs;
      matchState.extras.b += runs;
      matchState.balls += 1;

      if (runs % 2 === 1) {
        [matchState.striker, matchState.nonStriker] = [matchState.nonStriker, matchState.striker];
      }

      matchState.thisOver.push(`${runs}B`);

      if (matchState.balls === 6) {
        completeOver();
      }

      addToBallHistory({ type: 'bye', runs: runs, bowler: matchState.bowler });
      updateDisplay();
      autoSaveMatch();
    }

    function showLegByeModal() {
      document.getElementById('legByeModal').classList.add('active');
    }

    function processLegBye(runs) {
      closeModal('legByeModal');

      matchState.batsmen[matchState.striker].balls += 1;
      matchState.bowlers[matchState.bowler].balls += 1;
      matchState.score.runs += runs;
      matchState.extras.lb += runs;
      matchState.balls += 1;

      if (runs % 2 === 1) {
        [matchState.striker, matchState.nonStriker] = [matchState.nonStriker, matchState.striker];
      }

      matchState.thisOver.push(`${runs}LB`);

      if (matchState.balls === 6) {
        completeOver();
      }

      addToBallHistory({ type: 'legbye', runs: runs, bowler: matchState.bowler });
      updateDisplay();
      autoSaveMatch();
    }

    function showWicketModal() {
      console.log('showWicketModal called');
      if (!matchState.striker || !matchState.bowler) {
        console.log('Missing striker or bowler');
        return;
      }
      
      const modal = document.getElementById('wicketModal');
      const title = document.getElementById('wicketModalTitle');
      console.log('Modal element:', modal);
      
      if (matchState.freeHit) {
        title.textContent = 'Free Hit - Only Run Out Allowed';
        const buttons = document.getElementById('wicketTypeButtons');
        buttons.innerHTML = '<button class="modal-btn" onclick="showRunOutModal()">Run Out</button>';
      } else {
        title.textContent = 'Wicket Type';
        const buttons = document.getElementById('wicketTypeButtons');
        buttons.innerHTML = `
          <button class="modal-btn" onclick="selectWicketType('bowled')">Bowled</button>
          <button class="modal-btn" onclick="selectWicketType('caught')">Caught</button>
          <button class="modal-btn" onclick="selectWicketType('lbw')">LBW</button>
          <button class="modal-btn" onclick="selectWicketType('stumped')">Stumped</button>
          <button class="modal-btn" onclick="showRunOutModal()">Run Out</button>
          <button class="modal-btn" onclick="selectWicketType('hitwicket')">Hit Wicket</button>
        `;
      }
      
      modal.classList.add('active');
      console.log('Modal should be visible now');
    }

    function showRunOutModal() {
      closeModal('wicketModal');
      document.getElementById('runOutModal').classList.add('active');
    }

    function processRunOut(runs) {
      const batsmanSelect = document.getElementById('runOutBatsmanSelect');
      const outBatsman = batsmanSelect.value; // 'striker' or 'nonStriker'
      
      closeModal('runOutModal');
      haptic('heavy');
      
      // Award runs to striker (they were attempting the run)
      matchState.batsmen[matchState.striker].runs += runs;
      matchState.batsmen[matchState.striker].balls += 1;

      // Update bowler and score
      matchState.bowlers[matchState.bowler].runs += runs;
      matchState.bowlers[matchState.bowler].balls += 1;
      matchState.score.runs += runs;
      matchState.balls += 1;

      // Update partnership stats for run-out
      matchState.currentPartnership.runs += runs;
      matchState.currentPartnership.balls += 1;
      
      // Determine who got out
      const outPlayer = outBatsman === 'striker' ? matchState.striker : matchState.nonStriker;
      matchState.batsmen[outPlayer].out = true;
      matchState.batsmen[outPlayer].outType = 'run out';
      
      // Update score wickets
      matchState.score.wickets += 1;

      // Record fall of wicket
      const totalOvers = matchState.overs + (matchState.balls / 6);
      matchState.wicketsFallen.push({
        score: matchState.score.runs,
        player: outPlayer,
        runs: matchState.batsmen[outPlayer].runs,
        over: totalOvers.toFixed(1)
      });

      // Save partnership and start new one (will be initialized when new batsman comes in)
      if (matchState.currentPartnership.batsman1 && matchState.currentPartnership.batsman2) {
        matchState.partnerships.push({...matchState.currentPartnership});
      }

      // Determine strike for next ball
      // If odd runs completed, batsmen crossed
      // If even runs, they didn't cross or crossed back
      let battersCrossed = (runs % 2 === 1);

      // Store the current batsmen before making changes
      const currentStriker = matchState.striker;
      const currentNonStriker = matchState.nonStriker;

      // Handle batsman positioning after run out
      if (outBatsman === 'striker') {
        // Striker got out
        if (battersCrossed) {
          // They crossed, so non-striker is now at striker's end
          // New batsman will come to non-striker's end
          matchState.striker = currentNonStriker;
          // matchState.nonStriker will be set to new batsman in showBatsmanModal
        } else {
          // They didn't cross, striker stays at striker's end
          // New batsman replaces striker at striker's end
          // matchState.striker will be set to new batsman in showBatsmanModal
        }
      } else {
        // Non-striker got out
        if (battersCrossed) {
          // They crossed, so striker is now at non-striker's end
          // New batsman comes to striker's end
          matchState.nonStriker = currentStriker;
          // matchState.striker will be set to new batsman in showBatsmanModal
        } else {
          // They didn't cross, positions stay the same
          // New batsman replaces non-striker at non-striker's end
          // matchState.striker stays the same
          // matchState.nonStriker will be set to new batsman in showBatsmanModal
        }
      }
      
      // Add runs first, then wicket separately
      if (runs > 0) {
        matchState.thisOver.push(runs.toString());
      }
      matchState.thisOver.push('W');

      if (matchState.score.wickets >= matchState.setup.wicketsLimit) {
        updateDisplay();
        autoSaveMatch(); // Save the last wicket before completing innings
        handleInningsComplete();
        return;
      }
      
      const overComplete = (matchState.balls === 6);
      if (overComplete) {
        matchState.balls = 0;
        matchState.pendingOverComplete = true;
      } else {
        matchState.pendingOverComplete = false;
      }
      
      // Reset free hit after run out
      matchState.freeHit = false;
      
      addToBallHistory({ 
        type: 'wicket',
        wicketType: 'run out',
        outBatsman: outBatsman,
        runs: runs,
        striker: matchState.striker, 
        nonStriker: matchState.nonStriker,
        bowler: matchState.bowler,
        overComplete: overComplete
      });
      
      // Store who got out so we know who to replace
      matchState.runOutVictim = outPlayer;
      
      showBatsmanModal();
      updateDisplay();
    }

    function selectWicketType(wicketType) {
      closeModal('wicketModal');
      
      if (!matchState.striker || !matchState.bowler) return;
      
      haptic('heavy');
      
      matchState.batsmen[matchState.striker].out = true;
      matchState.batsmen[matchState.striker].outType = wicketType;
      
      // Credit bowler with wicket
      matchState.bowlers[matchState.bowler].wickets += 1;
      matchState.bowlers[matchState.bowler].balls += 1;
      
      matchState.score.wickets += 1;
      matchState.balls += 1;
      
      // Update partnership
      matchState.currentPartnership.balls += 1;
      
      // Record fall of wicket
      const totalOvers = matchState.overs + (matchState.balls / 6);
      matchState.wicketsFallen.push({
        score: matchState.score.runs,
        player: matchState.striker,
        runs: matchState.batsmen[matchState.striker].runs,
        over: totalOvers.toFixed(1)
      });
      
      // Save partnership and start new one
      if (matchState.currentPartnership.batsman1 && matchState.currentPartnership.batsman2) {
        matchState.partnerships.push({...matchState.currentPartnership});
      }
      
      matchState.thisOver.push('W');

      // Reset free hit after wicket
      matchState.freeHit = false;

      if (matchState.score.wickets >= matchState.setup.wicketsLimit) {
        updateDisplay();
        autoSaveMatch(); // Save the last wicket before completing innings
        handleInningsComplete();
        return;
      }
      
      const overComplete = (matchState.balls === 6);
      if (overComplete) {
        matchState.balls = 0;
        matchState.pendingOverComplete = true;
      } else {
        matchState.pendingOverComplete = false;
      }
      
      addToBallHistory({ 
        type: 'wicket',
        wicketType: wicketType,
        striker: matchState.striker, 
        bowler: matchState.bowler,
        overComplete: overComplete
      });
      
      showBatsmanModal();
      updateDisplay();
    }

    function showBatsmanModal() {
      const select = document.getElementById('newBatsmanSelect');
      const battingPlayers = matchState.setup[matchState.battingTeam].players;
      
      // Filter out the run out victim if set, otherwise filter current batsmen
      const excludePlayers = matchState.runOutVictim ? 
        [matchState.runOutVictim] : 
        [matchState.striker, matchState.nonStriker];
      
      select.innerHTML = battingPlayers
        .filter(p => !matchState.batsmen[p].out && !matchState.batsmen[p].retired && !excludePlayers.includes(p))
        .map(p => `<option value="${p}">${p}</option>`)
        .join('');
      document.getElementById('batsmanModal').classList.add('active');
      document.getElementById('batsmanModal').removeAttribute('data-mode');
    }

    function confirmNewBatsman() {
      const select = document.getElementById('newBatsmanSelect');
      const mode = document.getElementById('batsmanModal').getAttribute('data-mode');
      
      if (mode === 'second-innings-bat1') {
        matchState.striker = select.value;
        const battingPlayers = matchState.setup[matchState.battingTeam].players;
        select.innerHTML = battingPlayers
          .filter(p => !matchState.batsmen[p].out && !matchState.batsmen[p].retired && p !== matchState.striker)
          .map(p => `<option value="${p}">${p}</option>`)
          .join('');
        document.getElementById('batsmanModalTitle').textContent = 'Select Opening Batsman 2';
        document.getElementById('batsmanModal').setAttribute('data-mode', 'second-innings-bat2');
      } else if (mode === 'second-innings-bat2') {
        matchState.nonStriker = select.value;
        closeModal('batsmanModal');
        document.getElementById('batsmanModalTitle').textContent = 'New Batsman';
        
        const bowlerSelect = document.getElementById('newBowlerSelect');
        const bowlingPlayers = matchState.setup[matchState.bowlingTeam].players;
        bowlerSelect.innerHTML = bowlingPlayers
          .map(p => `<option value="${p}">${p}</option>`)
          .join('');
        document.getElementById('newOverModal').classList.add('active');
        document.getElementById('newOverModal').setAttribute('data-mode', 'second-innings');
      } else if (mode === 'retire-return') {
        matchState.striker = select.value;
        matchState.batsmen[matchState.striker].retired = false;
        closeModal('batsmanModal');
        document.getElementById('batsmanModalTitle').textContent = 'New Batsman';
        updateDisplay();
      } else {
        // Regular wicket or run out - replace the appropriate batsman
        const newBatsman = select.value;

        if (matchState.runOutVictim) {
          // Run out case - the positioning has already been set in processRunOut
          // We just need to place the new batsman in the correct position
          // The runOutVictim tells us who got out, and positioning was already adjusted

          // Find which position needs the new batsman
          if (!matchState.striker || matchState.striker === matchState.runOutVictim) {
            matchState.striker = newBatsman;
          } else if (!matchState.nonStriker || matchState.nonStriker === matchState.runOutVictim) {
            matchState.nonStriker = newBatsman;
          } else {
            // Fallback: replace based on who actually got out
            if (matchState.runOutVictim === matchState.striker) {
              matchState.striker = newBatsman;
            } else {
              matchState.nonStriker = newBatsman;
            }
          }
          matchState.runOutVictim = null; // Clear the flag
        } else {
          // Regular wicket - striker got out
          matchState.striker = newBatsman;
        }
        
        // Start new partnership
        matchState.currentPartnership = {
          runs: 0,
          balls: 0,
          batsman1: matchState.striker,
          batsman2: matchState.nonStriker
        };
        
        closeModal('batsmanModal');
        document.getElementById('batsmanModalTitle').textContent = 'New Batsman';
        
        if (matchState.pendingOverComplete) {
          matchState.pendingOverComplete = false;
          [matchState.striker, matchState.nonStriker] = [matchState.nonStriker, matchState.striker];
          showNewOverModal();
        } else {
          updateDisplay();
        }
      }
    }

    function completeOver() {
      matchState.overs += 1;
      matchState.balls = 0;
      matchState.thisOver = [];

      if (matchState.overs >= matchState.setup.oversPerInnings) {
        updateDisplay();
        autoSaveMatch(); // Save before completing innings
        handleInningsComplete();
        return;
      }
      
      [matchState.striker, matchState.nonStriker] = [matchState.nonStriker, matchState.striker];
      showNewOverModal();
    }

    function showNewOverModal() {
      const select = document.getElementById('newBowlerSelect');
      const bowlingPlayers = matchState.setup[matchState.bowlingTeam].players;
      select.innerHTML = bowlingPlayers
        .filter(p => p !== matchState.bowler)
        .map(p => `<option value="${p}">${p}</option>`)
        .join('');
      document.getElementById('newOverModal').classList.add('active');
      document.getElementById('newOverModal').removeAttribute('data-mode');
    }

    function confirmNewOver() {
      const select = document.getElementById('newBowlerSelect');
      
      if (matchState.bowler && matchState.bowlers[matchState.bowler]) {
        const prevBowler = matchState.bowlers[matchState.bowler];
        prevBowler.overs = Math.floor(prevBowler.balls / 6);
      }
      
      matchState.bowler = select.value;
      
      if (!matchState.bowlers[matchState.bowler]) {
        matchState.bowlers[matchState.bowler] = { overs: 0, balls: 0, runs: 0, wickets: 0 };
      }
      
      closeModal('newOverModal');
      document.getElementById('newOverModal').removeAttribute('data-mode');
      updateDisplay();
    }

    function swapStrike() {
      haptic('light');
      [matchState.striker, matchState.nonStriker] = [matchState.nonStriker, matchState.striker];
      updateDisplay();
    }

    function retireBatsman() {
      if (!matchState.striker) return;
      
      if (confirm(`Retire ${matchState.striker}? They can return later if needed.`)) {
        haptic('medium');
        matchState.batsmen[matchState.striker].retired = true;
        
        const select = document.getElementById('newBatsmanSelect');
        const battingPlayers = matchState.setup[matchState.battingTeam].players;
        select.innerHTML = battingPlayers
          .filter(p => !matchState.batsmen[p].out && !matchState.batsmen[p].retired && p !== matchState.nonStriker)
          .map(p => `<option value="${p}">${p}</option>`)
          .join('');
        
        document.getElementById('batsmanModalTitle').textContent = 'Replace Retired Batsman';
        document.getElementById('batsmanModal').classList.add('active');
        document.getElementById('batsmanModal').setAttribute('data-mode', 'retire-return');
      }
    }

    function undoLastBall() {
      if (matchState.ballHistory.length === 0) return;
      haptic('medium');
      
      const lastBall = matchState.ballHistory.pop();
      
      if (lastBall.type === 'legal') {
        matchState.batsmen[lastBall.striker].runs -= lastBall.runs;
        matchState.batsmen[lastBall.striker].balls -= 1;
        if (lastBall.runs === 4) matchState.batsmen[lastBall.striker].fours -= 1;
        if (lastBall.runs === 6) matchState.batsmen[lastBall.striker].sixes -= 1;
        matchState.bowlers[lastBall.bowler].runs -= lastBall.runs;
        matchState.bowlers[lastBall.bowler].balls -= 1;
        matchState.score.runs -= lastBall.runs;
        matchState.balls -= 1;
        
        if (lastBall.nonStriker) {
          matchState.striker = lastBall.striker;
          matchState.nonStriker = lastBall.nonStriker;
        }
      } else if (lastBall.type === 'noball') {
        matchState.batsmen[lastBall.striker].runs -= lastBall.runs;
        matchState.batsmen[lastBall.striker].balls -= 1;
        if (lastBall.runs === 4) matchState.batsmen[lastBall.striker].fours -= 1;
        if (lastBall.runs === 6) matchState.batsmen[lastBall.striker].sixes -= 1;
        matchState.bowlers[lastBall.bowler].runs -= (1 + lastBall.runs);
        matchState.score.runs -= (1 + lastBall.runs);
        matchState.extras.nb -= 1;
        matchState.freeHit = false;
      } else if (lastBall.type === 'bye' || lastBall.type === 'legbye') {
        matchState.batsmen[matchState.striker].balls -= 1;
        matchState.bowlers[lastBall.bowler].balls -= 1;
        matchState.score.runs -= lastBall.runs;
        if (lastBall.type === 'bye') {
          matchState.extras.b -= lastBall.runs;
        } else {
          matchState.extras.lb -= lastBall.runs;
        }
        matchState.balls -= 1;
      } else if (lastBall.type === 'wide') {
        matchState.bowlers[lastBall.bowler].runs -= lastBall.runs;
        matchState.score.runs -= lastBall.runs;
        matchState.extras.wd -= 1;  // Always subtract 1 wide, not the total runs
      } else if (lastBall.type === 'wicket') {
        // Handle run out undo
        if (lastBall.wicketType === 'run out' && lastBall.runs !== undefined) {
          // Undo runs
          matchState.batsmen[lastBall.striker].runs -= lastBall.runs;
          matchState.batsmen[lastBall.striker].balls -= 1;
          matchState.bowlers[lastBall.bowler].runs -= lastBall.runs;
          matchState.bowlers[lastBall.bowler].balls -= 1;
          matchState.score.runs -= lastBall.runs;
          matchState.balls -= 1;
          
          // Restore batsman positions
          if (lastBall.striker && lastBall.nonStriker) {
            matchState.striker = lastBall.striker;
            matchState.nonStriker = lastBall.nonStriker;
          }
          
          // Un-out the batsman who was run out
          const outBatsman = lastBall.outBatsman === 'striker' ? lastBall.striker : lastBall.nonStriker;
          matchState.batsmen[outBatsman].out = false;
          matchState.batsmen[outBatsman].outType = null;
        } else {
          // Regular wicket undo
          matchState.batsmen[lastBall.striker].out = false;
          matchState.batsmen[lastBall.striker].outType = null;
          if (lastBall.wicketType !== 'runout') {
            matchState.bowlers[lastBall.bowler].wickets -= 1;
          }
          matchState.bowlers[lastBall.bowler].balls -= 1;
          matchState.balls -= 1;
        }
        
        matchState.score.wickets -= 1;
      }
      
      matchState.thisOver.pop();
      updateDisplay();
    }

    function updateDisplay() {
      try {
        console.log('Updating display...');
        
        document.getElementById('mainScore').textContent = `${matchState.score.runs}/${matchState.score.wickets}`;
        
        const oversDisplay = `${matchState.overs}.${matchState.balls}`;
        document.getElementById('oversDisplay').textContent = oversDisplay;
        
        const totalOvers = matchState.overs + (matchState.balls / 6);
        const runRate = totalOvers > 0 ? (matchState.score.runs / totalOvers).toFixed(2) : '0.00';
        document.getElementById('runRate').textContent = runRate;
        
        document.getElementById('teamName').textContent = matchState.setup[matchState.battingTeam].name;
        
        const targetEl = document.getElementById('targetDisplay');
        if (matchState.innings === 2 && matchState.firstInningsScore) {
          const target = matchState.firstInningsScore + 1;
          const needed = target - matchState.score.runs;
          const ballsLeft = (matchState.setup.oversPerInnings * 6) - (matchState.overs * 6 + matchState.balls);
          const wicketsLeft = matchState.setup.wicketsLimit - matchState.score.wickets;
          
          if (needed > 0) {
            targetEl.textContent = `Target: ${target} | Need ${needed} runs from ${ballsLeft} balls (${wicketsLeft} wickets left)`;
            targetEl.style.display = 'block';
          } else {
            targetEl.textContent = `${matchState.setup[matchState.battingTeam].name} won by ${wicketsLeft} wickets!`;
            targetEl.style.display = 'block';
          }
        } else {
          targetEl.style.display = 'none';
        }
        
        document.getElementById('freeHitBadge').style.display = matchState.freeHit ? 'inline-block' : 'none';
        
        if (matchState.striker) {
          const strikerData = matchState.batsmen[matchState.striker];
          document.getElementById('striker').textContent = matchState.striker;
          document.getElementById('strikerStats').textContent = `${strikerData.runs}(${strikerData.balls})`;
        }
        
        if (matchState.nonStriker) {
          const nonStrikerData = matchState.batsmen[matchState.nonStriker];
          document.getElementById('nonStriker').textContent = matchState.nonStriker;
          document.getElementById('nonStrikerStats').textContent = `${nonStrikerData.runs}(${nonStrikerData.balls})`;
        }
        
        if (matchState.bowler) {
          const bowlerData = matchState.bowlers[matchState.bowler];
          const bowlerOvers = `${Math.floor(bowlerData.balls / 6)}.${bowlerData.balls % 6}`;
          document.getElementById('bowler').textContent = matchState.bowler;
          document.getElementById('bowlerStats').textContent = `${bowlerData.wickets}-${bowlerData.runs} (${bowlerOvers})`;
        }
        
        const thisOverEl = document.getElementById('thisOver');
        thisOverEl.innerHTML = matchState.thisOver.map(ball => {
          let className = '';
          if (ball === 'W') className = 'wicket';
          else if (ball === '4' || ball === '6') className = 'boundary';
          return `<div class="ball-badge ${className}">${ball}</div>`;
        }).join('');
        
        updateStatsTable();
      } catch (err) {
        console.error('Display update error:', err);
        alert('Display error occurred. Please refresh the page. Error: ' + err.message);
      }
    }

    function updateStatsTable() {
      // Match Summary
      document.getElementById('statCurrentScore').textContent = `${matchState.score.runs}/${matchState.score.wickets}`;
      const totalOvers = matchState.overs + (matchState.balls / 6);
      const runRate = totalOvers > 0 ? (matchState.score.runs / totalOvers).toFixed(2) : '0.00';
      document.getElementById('statRunRate').textContent = runRate;
      
      const oversRemaining = matchState.setup.oversPerInnings - totalOvers;
      document.getElementById('statOversRemaining').textContent = oversRemaining.toFixed(1);
      
      const projected = totalOvers > 0 ? Math.round((matchState.score.runs / totalOvers) * matchState.setup.oversPerInnings) : 0;
      document.getElementById('statProjected').textContent = projected;
      
      // Batting Stats
      const battingStatsEl = document.getElementById('battingStats');
      const battingPlayers = matchState.setup[matchState.battingTeam].players;
      
      battingStatsEl.innerHTML = battingPlayers.map(p => {
        const stats = matchState.batsmen[p];
        if (!stats || (stats.balls === 0 && !stats.out && !stats.retired)) return '';
        const sr = stats.balls > 0 ? ((stats.runs / stats.balls) * 100).toFixed(1) : '-';
        let status = '';
        if (stats.retired) {
          status = 'ret';
        } else if (stats.out) {
          status = stats.outType || 'out';
        } else if (p === matchState.striker || p === matchState.nonStriker) {
          status = 'batting*';
        } else {
          status = '-';
        }
        return `<tr><td>${p}</td><td><strong>${stats.runs}</strong></td><td>${stats.balls}</td><td>${stats.fours}</td><td>${stats.sixes}</td><td>${sr}</td><td>${status}</td></tr>`;
      }).filter(row => row).join('');
      
      // Partnerships
      updatePartnershipStats();
      
      // Bowling Stats
      const bowlingStatsEl = document.getElementById('bowlingStats');
      const bowlingPlayers = matchState.setup[matchState.bowlingTeam].players;
      
      bowlingStatsEl.innerHTML = bowlingPlayers
        .filter(p => matchState.bowlers[p] && matchState.bowlers[p].balls > 0)
        .map(p => {
          const stats = matchState.bowlers[p];
          const overs = `${Math.floor(stats.balls / 6)}.${stats.balls % 6}`;
          const totalOvers = stats.balls / 6;
          const econ = totalOvers > 0 ? (stats.runs / totalOvers).toFixed(2) : '0.00';
          const maidens = stats.maidens || 0;
          const dots = stats.dots || 0;
          return `<tr><td>${p}</td><td>${overs}</td><td>${maidens}</td><td>${stats.runs}</td><td><strong>${stats.wickets}</strong></td><td>${econ}</td><td>${dots}</td></tr>`;
        }).join('');
      
      // Extras
      const totalExtras = matchState.extras.nb + matchState.extras.wd + matchState.extras.b + matchState.extras.lb;
      document.getElementById('nbCount').textContent = matchState.extras.nb;
      document.getElementById('wdCount').textContent = matchState.extras.wd;
      document.getElementById('bCount').textContent = matchState.extras.b;
      document.getElementById('lbCount').textContent = matchState.extras.lb;
      document.getElementById('totalExtras').textContent = totalExtras;
      
      // Over phases
      updateOverPhases();
      
      // Milestones
      updateMilestones();
      
      // Fall of Wickets
      updateWicketFall();
    }
    
    function updatePartnershipStats() {
      const partnershipEl = document.getElementById('partnershipStats');
      
      // Show completed partnerships + current partnership
      let html = '';
      
      matchState.partnerships.forEach((p, idx) => {
        html += `<tr><td>${idx + 1}</td><td><strong>${p.runs}</strong></td><td>${p.balls}</td><td>${p.batsman1} & ${p.batsman2}</td></tr>`;
      });
      
      // Current partnership
      if (matchState.currentPartnership.batsman1 && matchState.currentPartnership.batsman2) {
        html += `<tr style="background: var(--accent-light);"><td>Current</td><td><strong>${matchState.currentPartnership.runs}</strong></td><td>${matchState.currentPartnership.balls}</td><td>${matchState.currentPartnership.batsman1} & ${matchState.currentPartnership.batsman2}</td></tr>`;
      }
      
      partnershipEl.innerHTML = html || '<tr><td colspan="4" style="text-align: center; color: var(--muted);">No partnerships yet</td></tr>';
    }
    
    function updateOverPhases() {
      const phaseEl = document.getElementById('overPhaseStats');
      const phases = [];
      const totalOvers = matchState.overs + (matchState.balls / 6);
      
      // Powerplay (first 6 overs)
      if (totalOvers > 0) {
        const ppOvers = Math.min(6, totalOvers);
        phases.push({ name: 'Powerplay', overs: `0-${ppOvers.toFixed(1)}`, runs: 0, wickets: 0 });
      }
      
      // Middle (7-15)
      if (totalOvers > 6) {
        const midOvers = Math.min(15, totalOvers) - 6;
        phases.push({ name: 'Middle', overs: `6-${(6 + midOvers).toFixed(1)}`, runs: 0, wickets: 0 });
      }
      
      // Death (16+)
      if (totalOvers > 15) {
        const deathOvers = totalOvers - 15;
        phases.push({ name: 'Death', overs: `15-${totalOvers.toFixed(1)}`, runs: 0, wickets: 0 });
      }
      
      // For now, show phases without detailed breakdowns (would need ball-by-ball tracking)
      phaseEl.innerHTML = phases.map(p => 
        `<tr><td>${p.name}</td><td>${p.overs}</td><td>-</td><td>-</td><td>-</td></tr>`
      ).join('') || '<tr><td colspan="5" style="text-align: center; color: var(--muted);">Not enough overs</td></tr>';
    }
    
    function updateMilestones() {
      const milestoneEl = document.getElementById('milestoneStats');
      
      if (matchState.milestones.length === 0) {
        milestoneEl.innerHTML = '<tr><td colspan="2" style="text-align: center; color: var(--muted);">No milestones yet</td></tr>';
        return;
      }
      
      milestoneEl.innerHTML = matchState.milestones.map(m => 
        `<tr><td>${m.player}</td><td><strong>${m.milestone} runs</strong> (${m.balls} balls)</td></tr>`
      ).join('');
    }
    
    function updateWicketFall() {
      const wicketEl = document.getElementById('wicketFall');
      
      if (matchState.wicketsFallen.length === 0) {
        wicketEl.innerHTML = '<tr><td colspan="2" style="text-align: center; color: var(--muted);">No wickets fallen</td></tr>';
        return;
      }
      
      wicketEl.innerHTML = matchState.wicketsFallen.map((w, idx) => 
        `<tr><td><strong>${w.score}/${idx + 1}</strong></td><td>${w.player} (${w.runs}) - ${w.over} ov</td></tr>`
      ).join('');
    }
    
    function checkMilestone(player, previousRuns, currentRuns) {
      const milestones = [50, 100, 150, 200];
      
      for (const milestone of milestones) {
        if (previousRuns < milestone && currentRuns >= milestone) {
          const balls = matchState.batsmen[player].balls;
          matchState.milestones.push({
            player: player,
            milestone: milestone,
            balls: balls
          });
        }
      }
    }

    function updateSettings() {
      document.getElementById('settingsFormat').textContent = matchState.setup.matchFormat;
      document.getElementById('oversInput').value = matchState.setup.oversPerInnings;
      document.getElementById('wicketsInput').value = matchState.setup.wicketsLimit;
      document.getElementById('settingsTeamA').textContent = matchState.setup.teamA.name;
      document.getElementById('settingsTeamB').textContent = matchState.setup.teamB.name;
    }

    function updateOvers() {
      const newOvers = parseInt(document.getElementById('oversInput').value);
      if (newOvers > 0 && newOvers <= 50) {
        matchState.setup.oversPerInnings = newOvers;
        localStorage.setItem('stumpvision_match', JSON.stringify(matchState.setup));
        showToast(`Overs updated to ${newOvers}`, 'success');
        updateDisplay();
      } else {
        showToast('Invalid overs value. Must be between 1 and 50', 'error');
      }
    }

    function updateWickets() {
      const newWickets = parseInt(document.getElementById('wicketsInput').value);
      if (newWickets > 0 && newWickets <= 11) {
        matchState.setup.wicketsLimit = newWickets;
        localStorage.setItem('stumpvision_match', JSON.stringify(matchState.setup));
        showToast(`Wickets limit updated to ${newWickets}`, 'success');
        updateDisplay();
      } else {
        showToast('Invalid wickets value. Must be between 1 and 11', 'error');
      }
    }

    function showPlayerManagement() {
      const modal = document.getElementById('playerManagementModal');
      
      document.getElementById('pmBattingTeamName').textContent = matchState.setup[matchState.battingTeam].name;
      document.getElementById('pmBowlingTeamName').textContent = matchState.setup[matchState.bowlingTeam].name;
      
      renderPlayerList('batting');
      renderPlayerList('bowling');
      
      modal.classList.add('active');
    }

    function renderPlayerList(teamType) {
      const team = teamType === 'batting' ? matchState.battingTeam : matchState.bowlingTeam;
      const players = matchState.setup[team].players;
      const container = teamType === 'batting' ?
        document.getElementById('battingTeamPlayers') :
        document.getElementById('bowlingTeamPlayers');

      // Clear existing content
      container.innerHTML = '';

      // Create player items using DOM manipulation
      players.forEach(p => {
        const stats = matchState.batsmen[p];
        const isActive = (p === matchState.striker || p === matchState.nonStriker || p === matchState.bowler);
        const isRetired = stats && stats.retired;

        const item = document.createElement('div');
        item.className = 'player-item';

        const nameContainer = document.createElement('span');
        nameContainer.className = 'player-item-name';
        nameContainer.textContent = p; // Safe

        if (isRetired) {
          const badge = document.createElement('span');
          badge.className = 'retired-badge';
          badge.textContent = 'RETIRED';
          nameContainer.appendChild(document.createTextNode(' '));
          nameContainer.appendChild(badge);
        }

        const actions = document.createElement('div');
        actions.className = 'player-item-actions';

        if (isRetired) {
          const unretireBtn = document.createElement('button');
          unretireBtn.className = 'player-item-btn';
          unretireBtn.textContent = 'Unretire';
          unretireBtn.setAttribute('aria-label', `Unretire ${p}`);
          unretireBtn.addEventListener('click', () => unretirePlayer(p));
          actions.appendChild(unretireBtn);
        }

        if (!isActive) {
          const removeBtn = document.createElement('button');
          removeBtn.className = 'player-item-btn danger';
          removeBtn.textContent = 'Remove';
          removeBtn.setAttribute('aria-label', `Remove ${p}`);
          removeBtn.addEventListener('click', () => removePlayer(team, p));
          actions.appendChild(removeBtn);
        }

        item.appendChild(nameContainer);
        item.appendChild(actions);
        container.appendChild(item);
      });
    }

    function addNewPlayer(teamType) {
      const inputId = teamType === 'batting' ? 'newBattingPlayer' : 'newBowlingPlayer';
      const input = document.getElementById(inputId);
      const playerName = input.value.trim();

      if (!playerName) {
        showToast('Please enter a player name', 'error');
        return;
      }

      const team = teamType === 'batting' ? matchState.battingTeam : matchState.bowlingTeam;

      if (matchState.setup[team].players.includes(playerName)) {
        showToast('Player already exists', 'error');
        return;
      }
      
      matchState.setup[team].players.push(playerName);
      matchState.batsmen[playerName] = { runs: 0, balls: 0, fours: 0, sixes: 0, out: false, outType: null, retired: false };
      matchState.bowlers[playerName] = { overs: 0, balls: 0, runs: 0, wickets: 0 };
      
      localStorage.setItem('stumpvision_match', JSON.stringify(matchState.setup));
      
      input.value = '';
      renderPlayerList(teamType);
      updateDisplay();
    }

    function removePlayer(team, playerName) {
      if (playerName === matchState.striker || playerName === matchState.nonStriker || playerName === matchState.bowler) {
        alert('Cannot remove active player');
        return;
      }
      
      if (confirm(`Remove ${playerName} from the match?`)) {
        const index = matchState.setup[team].players.indexOf(playerName);
        if (index > -1) {
          matchState.setup[team].players.splice(index, 1);
          localStorage.setItem('stumpvision_match', JSON.stringify(matchState.setup));
          
          const teamType = team === matchState.battingTeam ? 'batting' : 'bowling';
          renderPlayerList(teamType);
          updateDisplay();
        }
      }
    }

    function unretirePlayer(playerName) {
      matchState.batsmen[playerName].retired = false;
      const teamType = matchState.setup[matchState.battingTeam].players.includes(playerName) ? 'batting' : 'bowling';
      renderPlayerList(teamType);
      updateDisplay();
    }

    function showTab(tab) {
      haptic('light');
      document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      document.querySelector(`[onclick="showTab('${tab}')"]`).classList.add('active');
      document.getElementById(`${tab}Tab`).classList.add('active');
      
      // Hide scoring dock on Stats and Settings tabs
      const scoringDock = document.getElementById('scoringDock');
      if (tab === 'score') {
        scoringDock.classList.remove('hidden');
      } else {
        scoringDock.classList.add('hidden');
      }
    }

    function closeModal(modalId) {
      const modal = document.getElementById(modalId);
      modal.classList.remove('active');
      // Return focus to the last focused element before modal opened
      if (modal.lastFocusedElement) {
        modal.lastFocusedElement.focus();
        delete modal.lastFocusedElement;
      }
    }

    // Add global ESC key handler for modals
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        // Find active modal and close it
        const activeModal = document.querySelector('.modal.active');
        if (activeModal) {
          const modalId = activeModal.getAttribute('id');
          closeModal(modalId);
        }
      }
    });

    // Helper to open modal with focus management
    function openModal(modalId) {
      const modal = document.getElementById(modalId);
      // Store current focused element
      modal.lastFocusedElement = document.activeElement;
      modal.classList.add('active');

      // Focus first focusable element in modal
      setTimeout(() => {
        const focusable = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (focusable.length > 0) {
          focusable[0].focus();
        }
      }, 100);
    }

    function newInnings() {
      if (matchState.innings === 2) {
        alert('Match Complete!');
        return;
      }

      // Store complete first innings data before resetting
      matchState.firstInningsScore = matchState.score.runs;
      matchState.firstInningsData = {
        battingTeam: matchState.battingTeam,
        bowlingTeam: matchState.bowlingTeam,
        score: { ...matchState.score },
        overs: matchState.overs,
        balls: matchState.balls,
        batsmen: JSON.parse(JSON.stringify(matchState.batsmen)), // Deep copy
        bowlers: JSON.parse(JSON.stringify(matchState.bowlers)), // Deep copy
        extras: { ...matchState.extras },
        partnerships: [...matchState.partnerships],
        wicketsFallen: [...matchState.wicketsFallen],
        milestones: [...matchState.milestones]
      };

      matchState.innings = 2;
      [matchState.battingTeam, matchState.bowlingTeam] = [matchState.bowlingTeam, matchState.battingTeam];

      matchState.score = { runs: 0, wickets: 0 };
      matchState.overs = 0;
      matchState.balls = 0;
      matchState.thisOver = [];
      matchState.ballHistory = [];
      matchState.freeHit = false;
      matchState.striker = null;
      matchState.nonStriker = null;
      matchState.bowler = null;
      matchState.extras = { nb: 0, wd: 0, b: 0, lb: 0 };
      matchState.partnerships = [];
      matchState.wicketsFallen = [];
      matchState.milestones = [];
      matchState.currentPartnership = { runs: 0, balls: 0, batsman1: null, batsman2: null };

      const newBattingPlayers = matchState.setup[matchState.battingTeam].players;
      newBattingPlayers.forEach(p => {
        matchState.batsmen[p] = { runs: 0, balls: 0, fours: 0, sixes: 0, out: false, outType: null, retired: false, dots: 0 };
      });

      const newBowlingPlayers = matchState.setup[matchState.bowlingTeam].players;
      newBowlingPlayers.forEach(p => {
        if (!matchState.bowlers[p]) {
          matchState.bowlers[p] = { overs: 0, balls: 0, runs: 0, wickets: 0, dots: 0 };
        } else {
          matchState.bowlers[p] = { overs: 0, balls: 0, runs: 0, wickets: 0, dots: 0 };
        }
      });

      updateDisplay();

      const target = matchState.firstInningsScore + 1;
      alert(`Innings break!\n\n${matchState.setup[matchState.bowlingTeam].name} scored ${matchState.firstInningsScore} runs.\n\n${matchState.setup[matchState.battingTeam].name} needs ${target} runs to win!`);

      promptSecondInningsPlayers();
    }

    function promptSecondInningsPlayers() {
      const bat1Select = document.getElementById('newBatsmanSelect');
      const battingPlayers = matchState.setup[matchState.battingTeam].players;
      
      bat1Select.innerHTML = battingPlayers
        .filter(p => !matchState.batsmen[p].out && !matchState.batsmen[p].retired)
        .map(p => `<option value="${p}">${p}</option>`)
        .join('');
      
      document.getElementById('batsmanModalTitle').textContent = 'Select Opening Batsman 1';
      document.getElementById('batsmanModal').classList.add('active');
      document.getElementById('batsmanModal').setAttribute('data-mode', 'second-innings-bat1');
    }

    function resetMatch() {
      if (confirm('Reset the entire match? This cannot be undone.')) {
        localStorage.removeItem('stumpvision_match');
        window.location.href = 'setup.php';
      }
    }

    // Auto-save state tracker
    let lastAutoSaveTime = 0;
    let totalBallsRecorded = 0;

    async function autoSaveMatch() {
      totalBallsRecorded++;

      // Save on first ball to generate match ID immediately
      if (!matchState.saveId) {
        console.log('First ball - auto-saving to generate match ID');
        await saveMatch(true);
        return;
      }

      // After first save, save every over (every 6 balls) to keep data updated
      if (totalBallsRecorded % 6 === 0) {
        const now = Date.now();
        // Throttle: don't save more than once every 3 seconds to avoid rate limiting
        if (now - lastAutoSaveTime > 3000) {
          console.log('Auto-saving match after over completion');
          lastAutoSaveTime = now;
          await saveMatch(true);
        }
      }
    }

    async function saveMatch(silent = false) {
      const statusEl = document.getElementById('saveStatus');
      if (!silent) {
        statusEl.textContent = 'Saving...';
      }

      try {
        // Fetch CSRF token
        const tokenResponse = await fetch('api/matches.php?action=get-token');
        const tokenResult = await tokenResponse.json();

        if (!tokenResult.ok) {
          throw new Error('Failed to get CSRF token');
        }

        const payload = {
          meta: {
            title: `${matchState.setup.teamA.name} vs ${matchState.setup.teamB.name}`,
            oversPerSide: matchState.setup.oversPerInnings,
            ballsPerOver: 6,
            wicketsLimit: matchState.setup.wicketsLimit,
            scheduled_match_id: matchState.setup.loadedMatchId || null
          },
          teams: [
            { name: matchState.setup.teamA.name, players: matchState.setup.teamA.players },
            { name: matchState.setup.teamB.name, players: matchState.setup.teamB.players }
          ],
          innings: buildInningsData()
        };

        const response = await fetch('api/matches.php?action=save', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: matchState.saveId, payload: payload, csrf_token: tokenResult.token })
        });

        const result = await response.json();
        if (result.ok) {
          matchState.saveId = result.id;
          if (!silent) {
            statusEl.textContent = `Saved! ID: ${result.id}`;
            statusEl.style.color = 'var(--success)';
          }
          return true;
        } else {
          if (!silent) {
            statusEl.textContent = 'Save failed: ' + (result.err || 'Unknown error');
            statusEl.style.color = 'var(--danger)';
          }
          return false;
        }
      } catch (err) {
        console.error('Save error:', err);
        if (!silent) {
          statusEl.textContent = 'Save error: ' + err.message;
          statusEl.style.color = 'var(--danger)';
        }
        return false;
      }
    }

    function buildInningsData() {
      const innings = [];

      // If we're still in first innings, use current state
      if (matchState.innings === 1) {
        const inn1BattingTeam = matchState.battingTeam === 'teamA' ? 0 : 1;
        innings.push({
          batting: inn1BattingTeam,
          bowling: 1 - inn1BattingTeam,
          runs: matchState.score.runs,
          wickets: matchState.score.wickets,
          balls: matchState.overs * 6 + matchState.balls,
          extras: matchState.extras,
          batStats: buildBatStats(matchState.battingTeam),
          bowlStats: buildBowlStats(matchState.bowlingTeam)
        });
      } else {
        // Second innings: use saved first innings data to determine correct team assignment
        const inn1BattingTeam = matchState.firstInningsData.battingTeam === 'teamA' ? 0 : 1;

        innings.push({
          batting: inn1BattingTeam,
          bowling: 1 - inn1BattingTeam,
          runs: matchState.firstInningsScore || 0,
          wickets: matchState.firstInningsData.score.wickets,
          balls: matchState.firstInningsData.overs * 6 + matchState.firstInningsData.balls,
          extras: matchState.firstInningsData.extras,
          batStats: buildBatStatsFromData(matchState.firstInningsData.battingTeam, matchState.firstInningsData.batsmen),
          bowlStats: buildBowlStatsFromData(matchState.firstInningsData.bowlingTeam, matchState.firstInningsData.bowlers)
        });

        // Add second innings
        const inn2BattingTeam = 1 - inn1BattingTeam;
        innings.push({
          batting: inn2BattingTeam,
          bowling: inn1BattingTeam,
          runs: matchState.score.runs,
          wickets: matchState.score.wickets,
          balls: matchState.overs * 6 + matchState.balls,
          extras: matchState.extras,
          batStats: buildBatStats(matchState.battingTeam),
          bowlStats: buildBowlStats(matchState.bowlingTeam)
        });
      }

      return innings;
    }

    function buildBatStats(team) {
      const players = matchState.setup[team].players;
      return players.map(p => {
        // Handle both old format (string) and new format (object)
        const playerName = typeof p === 'string' ? p : p.name;
        const playerId = typeof p === 'object' ? p.playerId : null;
        const verified = typeof p === 'object' ? p.verified : false;

        const stats = matchState.batsmen[playerName] || { runs: 0, balls: 0, fours: 0, sixes: 0, out: false, outType: null, retired: false };
        return {
          name: playerName,
          playerId: playerId,
          verified: verified,
          runs: stats.runs,
          balls: stats.balls,
          fours: stats.fours,
          sixes: stats.sixes,
          out: stats.out,
          outType: stats.outType,
          retired: stats.retired
        };
      }).filter(s => s.balls > 0 || s.out || s.retired);
    }

    function buildBowlStats(team) {
      const players = matchState.setup[team].players;
      return players.map(p => {
        // Handle both old format (string) and new format (object)
        const playerName = typeof p === 'string' ? p : p.name;
        const playerId = typeof p === 'object' ? p.playerId : null;
        const verified = typeof p === 'object' ? p.verified : false;

        const stats = matchState.bowlers[playerName] || { balls: 0, runs: 0, wickets: 0 };
        return {
          name: playerName,
          playerId: playerId,
          verified: verified,
          balls: stats.balls,
          runs: stats.runs,
          wickets: stats.wickets
        };
      }).filter(s => s.balls > 0);
    }

    // Helper functions to build stats from saved first innings data
    function buildBatStatsFromData(team, batsmenData) {
      const players = matchState.setup[team].players;
      return players.map(p => {
        // Handle both old format (string) and new format (object)
        const playerName = typeof p === 'string' ? p : p.name;
        const playerId = typeof p === 'object' ? p.playerId : null;
        const verified = typeof p === 'object' ? p.verified : false;

        const stats = batsmenData[playerName] || { runs: 0, balls: 0, fours: 0, sixes: 0, out: false, outType: null, retired: false };
        return {
          name: playerName,
          playerId: playerId,
          verified: verified,
          runs: stats.runs,
          balls: stats.balls,
          fours: stats.fours,
          sixes: stats.sixes,
          out: stats.out,
          outType: stats.outType,
          retired: stats.retired
        };
      }).filter(s => s.balls > 0 || s.out || s.retired);
    }

    function buildBowlStatsFromData(team, bowlersData) {
      const players = matchState.setup[team].players;
      return players.map(p => {
        // Handle both old format (string) and new format (object)
        const playerName = typeof p === 'string' ? p : p.name;
        const playerId = typeof p === 'object' ? p.playerId : null;
        const verified = typeof p === 'object' ? p.verified : false;

        const stats = bowlersData[playerName] || { balls: 0, runs: 0, wickets: 0 };
        return {
          name: playerName,
          playerId: playerId,
          verified: verified,
          balls: stats.balls,
          runs: stats.runs,
          wickets: stats.wickets
        };
      }).filter(s => s.balls > 0);
    }

    async function shareRecap() {
      const statusEl = document.getElementById('saveStatus');

      // If match not yet saved, save it first to generate match ID
      if (!matchState.saveId) {
        statusEl.textContent = 'Saving match...';
        const saved = await saveMatch(false);
        if (!saved) {
          alert('Failed to save match. Cannot generate share card.');
          return;
        }
      }

      statusEl.textContent = 'Generating share card...';
      
      try {
        const response = await fetch(`api/renderCard.php?id=${encodeURIComponent(matchState.saveId)}`);
        const result = await response.json();
        
        if (!result.ok) {
          alert('Could not generate share card: ' + (result.error || 'Unknown error'));
          statusEl.textContent = 'Share failed';
          return;
        }
        
        const shareUrl = result.mp4 || result.fallback_png;
        if (!shareUrl) {
          alert('No share asset was generated');
          return;
        }
        
        const blob = await (await fetch(shareUrl)).blob();
        const file = new File([blob], result.mp4 ? 'StumpVision.mp4' : 'StumpVision.png', 
          { type: result.mp4 ? 'video/mp4' : 'image/png' });
        
        if (navigator.canShare && navigator.canShare({ files: [file] })) {
          try {
            await navigator.share({
              title: `${matchState.setup.teamA.name} vs ${matchState.setup.teamB.name}`,
              text: 'Match scorecard from StumpVision',
              files: [file]
            });
            statusEl.textContent = 'Shared!';
            return;
          } catch (shareErr) {}
        }
        
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = file.name;
        a.click();
        statusEl.textContent = 'Downloaded share card';
      } catch (err) {
        alert('Share error: ' + err.message);
        statusEl.textContent = 'Share failed';
      }
    }

    async function handleInningsComplete() {
      if (matchState.innings === 1) {
        alert('First Innings Complete!');
        newInnings();
      } else {
        await completeMatch();
      }
    }

    async function completeMatch() {
      try {
        // Mark match as completed so live viewers can redirect
        matchState.matchCompleted = true;

        // Use the saveMatch function which handles CSRF token
        const saved = await saveMatch(false);

        if (saved) {
          const payload = {
            meta: {
              title: `${matchState.setup.teamA.name} vs ${matchState.setup.teamB.name}`,
              oversPerSide: matchState.setup.oversPerInnings,
              ballsPerOver: 6,
              wicketsLimit: matchState.setup.wicketsLimit
            },
            teams: [
              { name: matchState.setup.teamA.name, players: matchState.setup.teamA.players },
              { name: matchState.setup.teamB.name, players: matchState.setup.teamB.players }
            ],
            innings: buildInningsData()
          };

          localStorage.setItem('stumpvision_completed_match', JSON.stringify({
            saveId: matchState.saveId,
            payload: payload
          }));

          alert('Match Complete!\n\nMatch saved successfully. Redirecting to summary...');

          window.location.href = 'summary.php';
        } else {
          alert('Match complete but save failed. Showing stats anyway...');
          showTab('stats');
        }
      } catch (err) {
        console.error('Error saving match:', err);
        alert('Match complete but save failed: ' + err.message);
        showTab('stats');
      }
    }

    // Live Score Sharing Functions
    let liveShareId = null;
    let liveUpdateInterval = null;

    async function startLiveSharing() {
      try {
        // If match not yet saved, save it first to generate match ID
        if (!matchState.saveId) {
          showToast('Saving match...', 'info');
          const saved = await saveMatch(false);
          if (!saved) {
            showToast('Failed to save match. Cannot start live sharing.', 'error');
            return;
          }
        }

        const response = await fetch('api/live.php?action=create', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            match_id: matchState.saveId,
            scheduled_match_id: matchState.setup.loadedMatchId || null
          })
        });

        const result = await response.json();

        if (!result.ok) {
          if (result.err === 'live_score_disabled') {
            showToast('Live score sharing is disabled on this server', 'error');
            return;
          }
          throw new Error(result.err || 'Failed to start live sharing');
        }

        liveShareId = result.live_id;
        const liveUrl = `${window.location.origin}${window.location.pathname.replace('index.php', '')}live.php?id=${liveShareId}`;

        document.getElementById('liveShareUrl').value = liveUrl;
        document.getElementById('startLiveBtn').style.display = 'none';
        document.getElementById('stopLiveBtn').style.display = 'block';
        document.getElementById('liveShareLink').style.display = 'block';

        showToast('Live sharing started!', 'success');

        // Start pushing updates every 5 seconds
        liveUpdateInterval = setInterval(pushLiveUpdate, 5000);
        pushLiveUpdate(); // Initial push

      } catch (err) {
        console.error('Error starting live sharing:', err);
        showToast('Failed to start live sharing', 'error');
      }
    }

    async function pushLiveUpdate() {
      if (!liveShareId) return;

      try {
        await fetch('api/live.php?action=update', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            live_id: liveShareId,
            state: matchState
          })
        });
      } catch (err) {
        console.error('Error pushing live update:', err);
      }
    }

    async function stopLiveSharing() {
      if (!liveShareId) return;

      try {
        await fetch('api/live.php?action=stop', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ live_id: liveShareId })
        });

        if (liveUpdateInterval) {
          clearInterval(liveUpdateInterval);
          liveUpdateInterval = null;
        }

        liveShareId = null;
        document.getElementById('startLiveBtn').style.display = 'block';
        document.getElementById('stopLiveBtn').style.display = 'none';
        document.getElementById('liveShareLink').style.display = 'none';

        showToast('Live sharing stopped', 'info');

      } catch (err) {
        console.error('Error stopping live sharing:', err);
        showToast('Failed to stop live sharing', 'error');
      }
    }

    function copyLiveLink() {
      const input = document.getElementById('liveShareUrl');
      input.select();
      input.setSelectionRange(0, 99999); // For mobile
      navigator.clipboard.writeText(input.value).then(() => {
        showToast('Link copied to clipboard!', 'success');
      }).catch(() => {
        showToast('Failed to copy link', 'error');
      });
    }

    // Check if live sharing is enabled and show the section
    async function checkLiveShareEnabled() {
      try {
        const response = await fetch('api/live.php?action=get&live_id=test');
        const result = await response.json();

        // If we get any response other than 403 forbidden, the feature might be enabled
        if (response.status !== 403) {
          document.getElementById('liveShareSection').style.display = 'block';
          const hint = document.querySelector('#liveShareSection .hint');
          if (hint) hint.style.display = 'none';
        }
      } catch (err) {
        // Live sharing not available
      }
    }

    // Check on init
    checkLiveShareEnabled();

    init();

    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js')
          .then(reg => console.log('Service worker registered'))
          .catch(err => console.log('Service worker registration failed:', err));
      });
    }
  </script>
</body>
</html>