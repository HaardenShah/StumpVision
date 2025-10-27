<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <meta name="theme-color" content="#ffffff">
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
    body { font-family: system-ui, -apple-system, sans-serif; background: var(--bg); color: var(--ink); line-height: 1.5; -webkit-font-smoothing: antialiased; }
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
    .tab-content { display: none; padding: 16px; padding-bottom: 180px; }
    .tab-content.active { display: block; }
    .current-players { background: var(--card); border: 2px solid var(--line); border-radius: 16px; padding: 16px; margin-bottom: 16px; }
    .player-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--line); }
    .player-row:last-child { border-bottom: none; }
    .player-name { font-weight: 700; }
    .player-stats { color: var(--muted); font-size: 14px; }
    .striker-badge { background: var(--accent-light); color: var(--accent); padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 700; margin-left: 8px; }
    .retired-badge { background: var(--muted); color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 700; margin-left: 8px; }
    .scoring-dock { position: fixed; bottom: 0; left: 0; right: 0; background: var(--card); border-top: 2px solid var(--line); padding: 12px; box-shadow: 0 -4px 20px var(--shadow); z-index: 50; }
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
    .modal-title { font-size: 18px; font-weight: 700; margin-bottom: 16px; }
    .modal-buttons { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 16px; }
    .modal-btn { padding: 14px; background: var(--card); border: 2px solid var(--line); border-radius: 12px; font-weight: 700; cursor: pointer; }
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
  </style>
</head>
<body>
  <div class="header">
    <div class="header-top">
      <div class="brand">StumpVision</div>
      <button class="settings-btn" onclick="showTab('settings')">Settings</button>
    </div>
    <div class="score-display">
      <div class="score-main" id="mainScore">0/0</div>
      <div class="score-meta">
        <span id="teamName">Team A</span>
        <span>Overs: <strong id="oversDisplay">0.0</strong></span>
        <span>RR: <strong id="runRate">0.00</strong></span>
      </div>
      <div id="targetDisplay" style="display: none; margin-top: 8px; font-size: 16px; font-weight: 600;"></div>
      <div id="freeHitBadge" class="free-hit-badge" style="display: none;">FREE HIT</div>
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

  <div id="statsTab" class="tab-content">
    <div class="stats-table">
      <div class="stats-title">Batting Stats</div>
      <table>
        <thead><tr><th>Player</th><th>R</th><th>B</th><th>4s</th><th>6s</th><th>SR</th><th>Out</th></tr></thead>
        <tbody id="battingStats"></tbody>
      </table>
    </div>
    <div class="stats-table">
      <div class="stats-title">Bowling Stats</div>
      <table>
        <thead><tr><th>Player</th><th>O</th><th>R</th><th>W</th><th>Econ</th></tr></thead>
        <tbody id="bowlingStats"></tbody>
      </table>
    </div>
    <div class="stats-table">
      <div class="stats-title">Extras</div>
      <table>
        <tbody>
          <tr><td>No Balls</td><td id="nbCount">0</td></tr>
          <tr><td>Wides</td><td id="wdCount">0</td></tr>
          <tr><td>Byes</td><td id="bCount">0</td></tr>
          <tr><td>Leg Byes</td><td id="lbCount">0</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div id="settingsTab" class="tab-content">
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
      <button class="btn-primary" onclick="newInnings()">Start New Innings</button>
      <button class="btn-primary" style="background: var(--danger);" onclick="resetMatch()">Reset Match</button>
    </div>
  </div>

  <div class="scoring-dock">
    <div class="pad-grid">
      <button class="pad-btn" onclick="recordBall(0)">0</button>
      <button class="pad-btn" onclick="recordBall(1)">1</button>
      <button class="pad-btn" onclick="recordBall(2)">2</button>
      <button class="pad-btn" onclick="recordBall(3)">3</button>
      <button class="pad-btn boundary" onclick="recordBall(4)">4</button>
      <button class="pad-btn boundary" onclick="recordBall(6)">6</button>
      <button class="pad-btn extra" onclick="showNoBallModal()">NB</button>
      <button class="pad-btn extra" onclick="showWideModal()">WD</button>
      <button class="pad-btn extra" onclick="showByeModal()">B</button>
      <button class="pad-btn extra" onclick="showLegByeModal()">LB</button>
      <button class="pad-btn wicket" onclick="showWicketModal()">W</button>
      <button class="pad-btn wicket" onclick="undoLastBall()">Undo</button>
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
      <div class="modal-title">Wide - Total runs?</div>
      <div class="modal-buttons">
        <button class="modal-btn" onclick="processWide(1)">1</button>
        <button class="modal-btn" onclick="processWide(2)">2</button>
        <button class="modal-btn" onclick="processWide(3)">3</button>
        <button class="modal-btn" onclick="processWide(4)">4</button>
        <button class="modal-btn" onclick="processWide(5)">5</button>
      </div>
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
        <button class="modal-btn" onclick="selectWicketType('runout')">Run Out</button>
        <button class="modal-btn" onclick="selectWicketType('hitwicket')">Hit Wicket</button>
      </div>
      <button class="btn-cancel" onclick="closeModal('wicketModal')">Cancel</button>
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
      saveId: null,
      pendingOverComplete: false
    };

    function addToBallHistory(entry) {
      matchState.ballHistory.push(entry);
      if (matchState.ballHistory.length > 50) {
        matchState.ballHistory.shift();
      }
    }

    function init() {
      console.log('Initializing...');
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
      
      matchState.setup.teamA.players.forEach(p => {
        matchState.batsmen[p] = { runs: 0, balls: 0, fours: 0, sixes: 0, out: false, outType: null, retired: false };
        matchState.bowlers[p] = { overs: 0, balls: 0, runs: 0, wickets: 0 };
      });
      matchState.setup.teamB.players.forEach(p => {
        matchState.batsmen[p] = { runs: 0, balls: 0, fours: 0, sixes: 0, out: false, outType: null, retired: false };
        matchState.bowlers[p] = { overs: 0, balls: 0, runs: 0, wickets: 0 };
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
        matchState.striker = battingPlayers[0];
        matchState.nonStriker = battingPlayers[1];
        matchState.bowler = bowlingPlayers[0];
      }
      console.log('Starting players:', { striker: matchState.striker, nonStriker: matchState.nonStriker, bowler: matchState.bowler });
    }

    function recordBall(runs) {
      console.log('Recording ball with runs:', runs);
      if (!matchState.striker || !matchState.bowler) {
        console.error('Missing striker or bowler');
        return;
      }
      
      haptic(runs === 4 || runs === 6 ? 'success' : 'light');
      
      matchState.batsmen[matchState.striker].runs += runs;
      matchState.batsmen[matchState.striker].balls += 1;
      if (runs === 4) matchState.batsmen[matchState.striker].fours += 1;
      if (runs === 6) matchState.batsmen[matchState.striker].sixes += 1;
      
      matchState.bowlers[matchState.bowler].runs += runs;
      matchState.bowlers[matchState.bowler].balls += 1;
      
      matchState.score.runs += runs;
      matchState.balls += 1;
      
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
      
      matchState.bowlers[matchState.bowler].runs += (1 + batRuns);
      matchState.score.runs += (1 + batRuns);
      matchState.extras.nb += 1;
      
      matchState.thisOver.push(`${batRuns}+NB`);
      matchState.freeHit = true;
      
      if (batRuns % 2 === 1) {
        [matchState.striker, matchState.nonStriker] = [matchState.nonStriker, matchState.striker];
      }
      
      addToBallHistory({ type: 'noball', runs: batRuns, striker: matchState.striker, bowler: matchState.bowler });
      updateDisplay();
    }

    function showWideModal() {
      document.getElementById('wideModal').classList.add('active');
    }

    function processWide(totalRuns) {
      closeModal('wideModal');
      haptic('light');
      
      matchState.bowlers[matchState.bowler].runs += totalRuns;
      matchState.score.runs += totalRuns;
      matchState.extras.wd += totalRuns;
      matchState.thisOver.push(`${totalRuns}WD`);
      
      addToBallHistory({ type: 'wide', runs: totalRuns, bowler: matchState.bowler });
      updateDisplay();
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
    }

    function showWicketModal() {
      if (!matchState.striker || !matchState.bowler) return;
      
      const modal = document.getElementById('wicketModal');
      const title = document.getElementById('wicketModalTitle');
      
      if (matchState.freeHit) {
        title.textContent = 'Free Hit - Only Run Out Allowed';
        const buttons = document.getElementById('wicketTypeButtons');
        buttons.innerHTML = '<button class="modal-btn" onclick="selectWicketType(\'runout\')">Run Out</button>';
      } else {
        title.textContent = 'Wicket Type';
        const buttons = document.getElementById('wicketTypeButtons');
        buttons.innerHTML = `
          <button class="modal-btn" onclick="selectWicketType('bowled')">Bowled</button>
          <button class="modal-btn" onclick="selectWicketType('caught')">Caught</button>
          <button class="modal-btn" onclick="selectWicketType('lbw')">LBW</button>
          <button class="modal-btn" onclick="selectWicketType('stumped')">Stumped</button>
          <button class="modal-btn" onclick="selectWicketType('runout')">Run Out</button>
          <button class="modal-btn" onclick="selectWicketType('hitwicket')">Hit Wicket</button>
        `;
      }
      
      modal.classList.add('active');
    }

    function selectWicketType(wicketType) {
      closeModal('wicketModal');
      recordWicket(wicketType);
    }

    function recordWicket(wicketType) {
      if (!matchState.striker || !matchState.bowler) return;
      
      haptic('heavy');
      
      matchState.batsmen[matchState.striker].out = true;
      matchState.batsmen[matchState.striker].outType = wicketType;
      
      // Only credit bowler for non-runout wickets
      if (wicketType !== 'runout') {
        matchState.bowlers[matchState.bowler].wickets += 1;
      }
      
      // All wickets count as a legal ball except run outs on non-legal deliveries
      if (!matchState.freeHit || wicketType === 'runout') {
        matchState.bowlers[matchState.bowler].balls += 1;
        matchState.balls += 1;
      }
      
      matchState.score.wickets += 1;
      matchState.thisOver.push('W');
      
      if (matchState.score.wickets >= matchState.setup.wicketsLimit) {
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
      
      // Reset free hit after wicket
      matchState.freeHit = false;
      
      addToBallHistory({ 
        type: 'wicket',
        wicketType: wicketType,
        striker: matchState.striker, 
        bowler: matchState.bowler,
        overComplete: overComplete
      });
      
      showBatsmanModal();
    }

    function showBatsmanModal() {
      const select = document.getElementById('newBatsmanSelect');
      const battingPlayers = matchState.setup[matchState.battingTeam].players;
      select.innerHTML = battingPlayers
        .filter(p => !matchState.batsmen[p].out && !matchState.batsmen[p].retired && p !== matchState.striker && p !== matchState.nonStriker)
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
        matchState.striker = select.value;
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
        matchState.extras.wd -= lastBall.runs;
      } else if (lastBall.type === 'wicket') {
        matchState.batsmen[lastBall.striker].out = false;
        matchState.batsmen[lastBall.striker].outType = null;
        if (lastBall.wicketType !== 'runout') {
          matchState.bowlers[lastBall.bowler].wickets -= 1;
        }
        matchState.bowlers[lastBall.bowler].balls -= 1;
        matchState.score.wickets -= 1;
        matchState.balls -= 1;
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
      const battingStatsEl = document.getElementById('battingStats');
      const battingPlayers = matchState.setup[matchState.battingTeam].players;
      
      battingStatsEl.innerHTML = battingPlayers.map(p => {
        const stats = matchState.batsmen[p];
        if (!stats) return '';
        const sr = stats.balls > 0 ? ((stats.runs / stats.balls) * 100).toFixed(2) : '0.00';
        let status = '';
        if (stats.retired) {
          status = ' (retired)';
        } else if (p === matchState.striker || p === matchState.nonStriker) {
          status = ' (batting)';
        }
        const outInfo = stats.out ? stats.outType || 'out' : (status ? '' : '-');
        return `<tr><td>${p}${status}</td><td>${stats.runs}</td><td>${stats.balls}</td><td>${stats.fours}</td><td>${stats.sixes}</td><td>${sr}</td><td>${outInfo}</td></tr>`;
      }).join('');
      
      const bowlingStatsEl = document.getElementById('bowlingStats');
      const bowlingPlayers = matchState.setup[matchState.bowlingTeam].players;
      
      bowlingStatsEl.innerHTML = bowlingPlayers
        .filter(p => matchState.bowlers[p] && matchState.bowlers[p].balls > 0)
        .map(p => {
          const stats = matchState.bowlers[p];
          const overs = `${Math.floor(stats.balls / 6)}.${stats.balls % 6}`;
          const totalOvers = stats.balls / 6;
          const econ = totalOvers > 0 ? (stats.runs / totalOvers).toFixed(2) : '0.00';
          return `<tr><td>${p}</td><td>${overs}</td><td>${stats.runs}</td><td>${stats.wickets}</td><td>${econ}</td></tr>`;
        }).join('');
      
      document.getElementById('nbCount').textContent = matchState.extras.nb;
      document.getElementById('wdCount').textContent = matchState.extras.wd;
      document.getElementById('bCount').textContent = matchState.extras.b;
      document.getElementById('lbCount').textContent = matchState.extras.lb;
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
        alert(`Overs updated to ${newOvers}`);
        updateDisplay();
      }
    }

    function updateWickets() {
      const newWickets = parseInt(document.getElementById('wicketsInput').value);
      if (newWickets > 0 && newWickets <= 11) {
        matchState.setup.wicketsLimit = newWickets;
        localStorage.setItem('stumpvision_match', JSON.stringify(matchState.setup));
        alert(`Wickets limit updated to ${newWickets}`);
        updateDisplay();
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
      
      container.innerHTML = players.map(p => {
        const stats = matchState.batsmen[p];
        const isActive = (p === matchState.striker || p === matchState.nonStriker || p === matchState.bowler);
        const isRetired = stats && stats.retired;
        
        let statusBadge = '';
        if (isRetired) {
          statusBadge = '<span class="retired-badge">RETIRED</span>';
        }
        
        return `
          <div class="player-item">
            <span class="player-item-name">${p} ${statusBadge}</span>
            <div class="player-item-actions">
              ${isRetired ? `<button class="player-item-btn" onclick="unretirePlayer('${p}')">Unretire</button>` : ''}
              ${!isActive ? `<button class="player-item-btn danger" onclick="removePlayer('${team}', '${p}')">Remove</button>` : ''}
            </div>
          </div>
        `;
      }).join('');
    }

    function addNewPlayer(teamType) {
      const inputId = teamType === 'batting' ? 'newBattingPlayer' : 'newBowlingPlayer';
      const input = document.getElementById(inputId);
      const playerName = input.value.trim();
      
      if (!playerName) {
        alert('Please enter a player name');
        return;
      }
      
      const team = teamType === 'batting' ? matchState.battingTeam : matchState.bowlingTeam;
      
      if (matchState.setup[team].players.includes(playerName)) {
        alert('Player already exists');
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
    }

    function closeModal(modalId) {
      document.getElementById(modalId).classList.remove('active');
    }

    function newInnings() {
      if (matchState.innings === 2) {
        alert('Match Complete!');
        return;
      }
      
      matchState.firstInningsScore = matchState.score.runs;
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
      
      const newBattingPlayers = matchState.setup[matchState.battingTeam].players;
      newBattingPlayers.forEach(p => {
        matchState.batsmen[p] = { runs: 0, balls: 0, fours: 0, sixes: 0, out: false, outType: null, retired: false };
      });
      
      const newBowlingPlayers = matchState.setup[matchState.bowlingTeam].players;
      newBowlingPlayers.forEach(p => {
        if (!matchState.bowlers[p]) {
          matchState.bowlers[p] = { overs: 0, balls: 0, runs: 0, wickets: 0 };
        } else {
          matchState.bowlers[p] = { overs: 0, balls: 0, runs: 0, wickets: 0 };
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

    async function saveMatch() {
      const statusEl = document.getElementById('saveStatus');
      statusEl.textContent = 'Saving...';
      
      try {
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
        
        const response = await fetch('api/matches.php?action=save', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: matchState.saveId, payload: payload })
        });
        
        const result = await response.json();
        if (result.ok) {
          matchState.saveId = result.id;
          statusEl.textContent = `Saved! ID: ${result.id}`;
          statusEl.style.color = 'var(--success)';
        } else {
          statusEl.textContent = 'Save failed';
          statusEl.style.color = 'var(--danger)';
        }
      } catch (err) {
        statusEl.textContent = 'Save error: ' + err.message;
        statusEl.style.color = 'var(--danger)';
      }
    }

    function buildInningsData() {
      const innings = [];
      const inn1BattingTeam = matchState.battingTeam === 'teamA' ? 0 : 1;
      
      innings.push({
        batting: inn1BattingTeam,
        bowling: 1 - inn1BattingTeam,
        runs: matchState.firstInningsScore || 0,
        wickets: matchState.setup.wicketsLimit,
        balls: matchState.setup.oversPerInnings * 6,
        extras: matchState.extras,
        batStats: buildBatStats(matchState.battingTeam),
        bowlStats: buildBowlStats(matchState.bowlingTeam)
      });
      
      if (matchState.innings === 2) {
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
        const stats = matchState.batsmen[p] || { runs: 0, balls: 0, fours: 0, sixes: 0, out: false, outType: null, retired: false };
        return { 
          name: p, 
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
        const stats = matchState.bowlers[p] || { balls: 0, runs: 0, wickets: 0 };
        return { name: p, balls: stats.balls, runs: stats.runs, wickets: stats.wickets };
      }).filter(s => s.balls > 0);
    }

    async function shareRecap() {
      if (!matchState.saveId) {
        alert('Please save the match first before sharing!');
        return;
      }
      
      const statusEl = document.getElementById('saveStatus');
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

        const response = await fetch('api/matches.php?action=save', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: matchState.saveId, payload: payload })
        });

        const result = await response.json();
        
        if (result.ok) {
          matchState.saveId = result.id;
          
          localStorage.setItem('stumpvision_completed_match', JSON.stringify({
            saveId: result.id,
            payload: payload
          }));

          alert('🏆 Match Complete!\n\nMatch saved successfully. Redirecting to summary...');
          
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