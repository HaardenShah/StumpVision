<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <meta name="theme-color" content="#ffffff">
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
      overflow-x: hidden;
    }

    /* Header */
    .header {
      position: sticky;
      top: 0;
      z-index: 100;
      background: var(--card);
      border-bottom: 2px solid var(--line);
      box-shadow: 0 2px 8px var(--shadow);
    }

    .header-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 16px;
    }

    .brand {
      font-size: 20px;
      font-weight: 800;
    }

    .settings-btn {
      background: var(--card);
      border: 2px solid var(--line);
      color: var(--ink);
      padding: 8px 16px;
      border-radius: 999px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }

    .settings-btn:active {
      transform: scale(0.95);
    }

    /* Score display */
    .score-display {
      padding: 20px 16px;
      text-align: center;
      background: linear-gradient(135deg, var(--accent-light), var(--card));
    }

    .score-main {
      font-size: 48px;
      font-weight: 900;
      letter-spacing: -0.02em;
      margin-bottom: 8px;
    }

    .score-meta {
      display: flex;
      justify-content: center;
      gap: 20px;
      color: var(--muted);
      font-size: 14px;
      font-weight: 600;
    }

    .free-hit-badge {
      background: var(--danger);
      color: white;
      padding: 6px 12px;
      border-radius: 999px;
      font-size: 13px;
      font-weight: 700;
      display: inline-block;
      margin-top: 8px;
      animation: pulse 1.5s infinite;
    }

    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.7; }
    }

    /* Tabs */
    .tabs {
      display: flex;
      background: var(--card);
      border-bottom: 2px solid var(--line);
      overflow-x: auto;
    }

    .tab {
      flex: 1;
      padding: 14px 16px;
      border: none;
      background: none;
      color: var(--muted);
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      border-bottom: 3px solid transparent;
      white-space: nowrap;
    }

    .tab.active {
      color: var(--accent);
      border-bottom-color: var(--accent);
    }

    /* Content */
    .tab-content {
      display: none;
      padding: 16px;
      padding-bottom: 180px;
    }

    .tab-content.active {
      display: block;
      animation: fadeIn 0.3s;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Current players */
    .current-players {
      background: var(--card);
      border: 2px solid var(--line);
      border-radius: 16px;
      padding: 16px;
      margin-bottom: 16px;
    }

    .player-row {
      display: flex;
      justify-content: space-between;
      padding: 10px 0;
      border-bottom: 1px solid var(--line);
    }

    .player-row:last-child {
      border-bottom: none;
    }

    .player-name {
      font-weight: 700;
    }

    .player-stats {
      color: var(--muted);
      font-size: 14px;
    }

    .striker-badge {
      background: var(--accent-light);
      color: var(--accent);
      padding: 2px 8px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 700;
      margin-left: 8px;
    }

    /* Scoring pad */
    .scoring-dock {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: var(--card);
      border-top: 2px solid var(--line);
      padding: 12px 12px calc(12px + env(safe-area-inset-bottom));
      box-shadow: 0 -4px 20px var(--shadow);
      z-index: 50;
    }

    .pad-grid {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 8px;
      max-width: 800px;
      margin: 0 auto;
    }

    @media (max-width: 640px) {
      .pad-grid {
        grid-template-columns: repeat(4, 1fr);
      }
    }

    .pad-btn {
      background: var(--card);
      border: 2px solid var(--line);
      color: var(--ink);
      padding: 16px 8px;
      border-radius: 12px;
      font-size: 20px;
      font-weight: 800;
      cursor: pointer;
      transition: all 0.15s;
      position: relative;
      overflow: hidden;
      min-height: 56px;
    }

    .pad-btn:active {
      transform: scale(0.95);
    }

    .pad-btn.boundary {
      background: var(--success-light);
      border-color: var(--success);
      color: var(--success);
    }

    .pad-btn.wicket {
      background: var(--danger-light);
      border-color: var(--danger);
      color: var(--danger);
    }

    .pad-btn.extra {
      background: var(--accent-light);
      border-color: var(--accent);
      color: var(--accent);
    }

    /* Stats table */
    .stats-table {
      background: var(--card);
      border: 2px solid var(--line);
      border-radius: 16px;
      overflow: hidden;
      margin-bottom: 16px;
    }

    .stats-title {
      padding: 12px 16px;
      background: var(--accent-light);
      font-weight: 700;
      color: var(--accent);
      border-bottom: 2px solid var(--line);
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th, td {
      padding: 10px 12px;
      text-align: left;
      font-size: 14px;
    }

    th {
      background: var(--card);
      font-weight: 700;
      color: var(--muted);
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      border-bottom: 2px solid var(--line);
    }

    td {
      border-bottom: 1px solid var(--line);
    }

    tr:last-child td {
      border-bottom: none;
    }

    tbody tr:hover {
      background: var(--card);
    }

    /* Modal */
    .modal {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.7);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 200;
      backdrop-filter: blur(4px);
      padding: 20px;
    }

    .modal.active {
      display: flex;
    }

    .modal-content {
      background: var(--card);
      border: 2px solid var(--line);
      border-radius: 20px;
      padding: 24px;
      max-width: 400px;
      width: 100%;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
      animation: modalPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes modalPop {
      from { opacity: 0; transform: scale(0.9); }
      to { opacity: 1; transform: scale(1); }
    }

    .modal-title {
      font-size: 18px;
      font-weight: 700;
      margin-bottom: 16px;
    }

    .modal-buttons {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 10px;
      margin-bottom: 16px;
    }

    .modal-btn {
      padding: 14px;
      background: var(--card);
      border: 2px solid var(--line);
      border-radius: 12px;
      font-weight: 700;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.15s;
    }

    .modal-btn:hover {
      border-color: var(--accent);
      background: var(--accent-light);
    }

    .modal-btn:active {
      transform: scale(0.95);
    }

    .btn-cancel {
      background: var(--danger-light);
      border-color: var(--danger);
      color: var(--danger);
      padding: 12px;
      border-radius: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.15s;
    }

    select.modal-select {
      width: 100%;
      padding: 12px;
      border: 2px solid var(--line);
      border-radius: 12px;
      background: var(--bg);
      color: var(--ink);
      font-size: 15px;
      margin-bottom: 16px;
    }

    select.modal-select:focus {
      outline: none;
      border-color: var(--accent);
    }

    .btn-primary {
      width: 100%;
      padding: 14px;
      background: var(--accent);
      border: none;
      border-radius: 12px;
      color: white;
      font-weight: 700;
      cursor: pointer;
      margin-bottom: 8px;
    }

    .over-display {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
      margin-top: 8px;
    }

    .ball-badge {
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--card);
      border: 2px solid var(--line);
      border-radius: 8px;
      font-weight: 700;
      font-size: 14px;
    }

    .ball-badge.boundary {
      background: var(--success-light);
      border-color: var(--success);
      color: var(--success);
    }

    .ball-badge.wicket {
      background: var(--danger-light);
      border-color: var(--danger);
      color: var(--danger);
    }

    .settings-panel {
      padding: 16px;
    }

    .settings-item {
      background: var(--card);
      border: 2px solid var(--line);
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 12px;
    }

    .settings-item h3 {
      font-size: 16px;
      margin-bottom: 12px;
    }

    .settings-item p {
      color: var(--muted);
      font-size: 14px;
      margin-bottom: 8px;
    }
  </style>
</head>
<body>
  <!-- Header -->
  <div class="header">
    <div class="header-top">
      <div class="brand">StumpVision</div>
      <button class="settings-btn" onclick="showTab('settings')">Settings</button>
    </div>

    <!-- Score Display -->
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

    <!-- Tabs -->
    <div class="tabs">
      <button class="tab active" onclick="showTab('score')">Score</button>
      <button class="tab" onclick="showTab('stats')">Stats</button>
      <button class="tab" onclick="showTab('settings')">Settings</button>
    </div>
  </div>

  <!-- Score Tab -->
  <div id="scoreTab" class="tab-content active">
    <div class="current-players">
      <div class="player-row">
        <div>
          <span class="player-name" id="striker">Striker</span>
          <span class="striker-badge">*</span>
        </div>
        <div class="player-stats" id="strikerStats">0(0)</div>
      </div>
      <div class="player-row">
        <div>
          <span class="player-name" id="nonStriker">Non-Striker</span>
        </div>
        <div class="player-stats" id="nonStrikerStats">0(0)</div>
      </div>
      <div class="player-row">
        <div>
          <span class="player-name" id="bowler">Bowler</span>
        </div>
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
      <button class="btn-primary" onclick="undoLastBall()">Undo Last Ball</button>
    </div>
  </div>

  <!-- Stats Tab -->
  <div id="statsTab" class="tab-content">
    <div class="stats-table">
      <div class="stats-title">Bowling Stats</div>
      <table>
        <thead>
          <tr>
            <th>Player</th>
            <th>O</th>
            <th>R</th>
            <th>W</th>
            <th>Econ</th>
          </tr>
        </thead>
        <tbody id="bowlingStats"></tbody>
      </table>
    </div>

    <div class="stats-table">
      <div class="stats-title">Extras</div>
      <table>
        <thead>
          <tr>
            <th>Type</th>
            <th>Count</th>
          </tr>
        </thead>
        <tbody id="extrasStats">
          <tr><td>No Balls</td><td id="nbCount">0</td></tr>
          <tr><td>Wides</td><td id="wdCount">0</td></tr>
          <tr><td>Byes</td><td id="bCount">0</td></tr>
          <tr><td>Leg Byes</td><td id="lbCount">0</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Settings Tab -->
  <div id="settingsTab" class="tab-content">
    <div class="settings-panel">
      <div class="settings-item">
        <h3>Match Info</h3>
        <p><strong>Format:</strong> <span id="settingsFormat">Limited Overs</span></p>
        <p><strong>Overs:</strong> <span id="settingsOvers">20</span></p>
        <p><strong>Wickets:</strong> <span id="settingsWickets">10</span></p>
      </div>

      <div class="settings-item">
        <h3>Teams</h3>
        <p><strong>Team A:</strong> <span id="settingsTeamA">Team A</span></p>
        <p><strong>Team B:</strong> <span id="settingsTeamB">Team B</span></p>
      </div>

      <button class="btn-primary" onclick="newInnings()">Start New Innings</button>
      <button class="btn-primary" onclick="endMatch()">End Match</button>
      <button class="btn-primary" style="background: var(--danger);" onclick="resetMatch()">Reset Match</button>
    </div>
  </div>

  <!-- Scoring Pad -->
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
      <button class="pad-btn wicket" onclick="recordWicket()">W</button>
      <button class="pad-btn wicket" onclick="undoLastBall()">Undo</button>
    </div>
  </div>

  <!-- No Ball Modal -->
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

  <!-- Wide Modal -->
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

  <!-- Bye Modal -->
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

  <!-- Leg Bye Modal -->
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

  <!-- New Over Modal -->
  <div id="newOverModal" class="modal">
    <div class="modal-content">
      <div class="modal-title">Select New Bowler</div>
      <select id="newBowlerSelect" class="modal-select"></select>
      <button class="btn-primary" onclick="confirmNewOver()">Continue</button>
    </div>
  </div>

  <!-- Select Batsman Modal -->
  <div id="batsmanModal" class="modal">
    <div class="modal-content">
      <div class="modal-title" id="batsmanModalTitle">New Batsman</div>
      <select id="newBatsmanSelect" class="modal-select"></select>
      <button class="btn-primary" onclick="confirmNewBatsman()">Continue</button>
    </div>
  </div>

  <script>
    // Haptic feedback helper - works on iOS and Android
    function haptic(style = 'light') {
      try {
        // iOS Haptic Feedback (Taptic Engine)
        if (window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.haptic) {
          window.webkit.messageHandlers.haptic.postMessage(style);
        }
        // Android Vibration API
        else if ('vibrate' in navigator) {
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

    // Match state
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
      firstInningsScore: null
    };

    // Initialize
    function init() {
      const saved = localStorage.getItem('stumpvision_match');
      if (!saved) {
        window.location.href = 'setup.php';
        return;
      }
      
      matchState.setup = JSON.parse(saved);
      
      // Determine batting and bowling teams based on toss
      if (matchState.setup.tossDecision === 'bat') {
        matchState.battingTeam = matchState.setup.tossWinner;
        matchState.bowlingTeam = matchState.setup.tossWinner === 'teamA' ? 'teamB' : 'teamA';
      } else {
        matchState.bowlingTeam = matchState.setup.tossWinner;
        matchState.battingTeam = matchState.setup.tossWinner === 'teamA' ? 'teamB' : 'teamA';
      }
      
      // Initialize batsmen and bowlers
      matchState.setup.teamA.players.forEach(p => {
        matchState.batsmen[p] = { runs: 0, balls: 0, fours: 0, sixes: 0, out: false };
      });
      matchState.setup.teamB.players.forEach(p => {
        matchState.batsmen[p] = { runs: 0, balls: 0, fours: 0, sixes: 0, out: false };
        matchState.bowlers[p] = { overs: 0, balls: 0, runs: 0, wickets: 0 };
      });
      
      // Prompt for starting players
      promptStartingPlayers();
      updateDisplay();
      updateSettings();
    }

    function promptStartingPlayers() {
      // Use the opening players from setup if available
      if (matchState.setup.openingBat1 && matchState.setup.openingBat2 && matchState.setup.openingBowler) {
        matchState.striker = matchState.setup.openingBat1;
        matchState.nonStriker = matchState.setup.openingBat2;
        matchState.bowler = matchState.setup.openingBowler;
      } else {
        // Fallback to first players
        const battingPlayers = matchState.setup[matchState.battingTeam].players;
        const bowlingPlayers = matchState.setup[matchState.bowlingTeam].players;
        
        matchState.striker = battingPlayers[0];
        matchState.nonStriker = battingPlayers[1];
        matchState.bowler = bowlingPlayers[0];
      }
    }

    // Record ball
    function recordBall(runs) {
      if (!matchState.striker || !matchState.bowler) return;
      
      haptic(runs === 4 || runs === 6 ? 'success' : 'light');
      
      const ball = {
        type: 'legal',
        runs: runs,
        striker: matchState.striker,
        bowler: matchState.bowler,
        freeHit: matchState.freeHit
      };
      
      // Update batsman
      matchState.batsmen[matchState.striker].runs += runs;
      matchState.batsmen[matchState.striker].balls += 1;
      if (runs === 4) matchState.batsmen[matchState.striker].fours += 1;
      if (runs === 6) matchState.batsmen[matchState.striker].sixes += 1;
      
      // Update bowler
      matchState.bowlers[matchState.bowler].runs += runs;
      matchState.bowlers[matchState.bowler].balls += 1;
      
      // Update score
      matchState.score.runs += runs;
      matchState.balls += 1;
      
      // Swap strike on odd runs
      if (runs % 2 === 1) {
        swapStrike();
      }
      
      // Add to over
      matchState.thisOver.push(runs === 0 ? '•' : runs.toString());
      
      // Check for over completion
      if (matchState.balls === 6) {
        completeOver();
      }
      
      // Reset free hit
      matchState.freeHit = false;
      
      matchState.ballHistory.push(ball);
      updateDisplay();
    }

    // No ball
    function showNoBallModal() {
      document.getElementById('noBallModal').classList.add('active');
    }

    function processNoBall(batRuns) {
      closeModal('noBallModal');
      haptic('medium');
      
      const ball = {
        type: 'noball',
        runs: batRuns,
        striker: matchState.striker,
        bowler: matchState.bowler
      };
      
      // Update scores
      matchState.batsmen[matchState.striker].runs += batRuns;
      if (batRuns === 4) matchState.batsmen[matchState.striker].fours += 1;
      if (batRuns === 6) matchState.batsmen[matchState.striker].sixes += 1;
      
      matchState.bowlers[matchState.bowler].runs += (1 + batRuns);
      matchState.score.runs += (1 + batRuns);
      matchState.extras.nb += 1;
      
      // No ball doesn't count toward over
      matchState.thisOver.push(`${batRuns}+NB`);
      
      // Set free hit
      matchState.freeHit = true;
      
      // Swap strike on odd bat runs
      if (batRuns % 2 === 1) {
        swapStrike();
      }
      
      matchState.ballHistory.push(ball);
      updateDisplay();
    }

    // Wide
    function showWideModal() {
      document.getElementById('wideModal').classList.add('active');
    }

    function processWide(totalRuns) {
      closeModal('wideModal');
      haptic('light');
      
      const ball = {
        type: 'wide',
        runs: totalRuns,
        bowler: matchState.bowler
      };
      
      matchState.bowlers[matchState.bowler].runs += totalRuns;
      matchState.score.runs += totalRuns;
      matchState.extras.wd += totalRuns;
      
      matchState.thisOver.push(`${totalRuns}WD`);
      
      matchState.ballHistory.push(ball);
      updateDisplay();
    }

    // Byes
    function showByeModal() {
      document.getElementById('byeModal').classList.add('active');
    }

    function processBye(runs) {
      closeModal('byeModal');
      
      const ball = {
        type: 'bye',
        runs: runs,
        bowler: matchState.bowler
      };
      
      matchState.bowlers[matchState.bowler].balls += 1;
      matchState.score.runs += runs;
      matchState.extras.b += runs;
      matchState.balls += 1;
      
      if (runs % 2 === 1) swapStrike();
      
      matchState.thisOver.push(`${runs}B`);
      
      if (matchState.balls === 6) {
        completeOver();
      }
      
      matchState.ballHistory.push(ball);
      updateDisplay();
    }

    // Leg Byes
    function showLegByeModal() {
      document.getElementById('legByeModal').classList.add('active');
    }

    function processLegBye(runs) {
      closeModal('legByeModal');
      
      const ball = {
        type: 'legbye',
        runs: runs,
        bowler: matchState.bowler
      };
      
      matchState.bowlers[matchState.bowler].balls += 1;
      matchState.score.runs += runs;
      matchState.extras.lb += runs;
      matchState.balls += 1;
      
      if (runs % 2 === 1) swapStrike();
      
      matchState.thisOver.push(`${runs}LB`);
      
      if (matchState.balls === 6) {
        completeOver();
      }
      
      matchState.ballHistory.push(ball);
      updateDisplay();
    }

    // Wicket
    function recordWicket() {
      if (!matchState.striker || !matchState.bowler) return;
      if (matchState.freeHit) {
        haptic('error');
        alert('No wicket on free hit! (Except run out)');
        return;
      }
      
      haptic('heavy');
      
      const ball = {
        type: 'wicket',
        striker: matchState.striker,
        bowler: matchState.bowler
      };
      
      matchState.batsmen[matchState.striker].out = true;
      matchState.bowlers[matchState.bowler].wickets += 1;
      matchState.bowlers[matchState.bowler].balls += 1;
      matchState.score.wickets += 1;
      matchState.balls += 1;
      
      matchState.thisOver.push('W');
      
      // Check if innings over
      if (matchState.score.wickets >= matchState.setup.wicketsLimit) {
        alert('Innings Complete!');
        newInnings();
        return;
      }
      
      if (matchState.balls === 6) {
        completeOver();
      }
      
      matchState.ballHistory.push(ball);
      
      // Select new batsman
      showBatsmanModal();
    }

    function showBatsmanModal() {
      const select = document.getElementById('newBatsmanSelect');
      const battingPlayers = matchState.setup[matchState.battingTeam].players;
      
      select.innerHTML = battingPlayers
        .filter(p => !matchState.batsmen[p].out && p !== matchState.striker && p !== matchState.nonStriker)
        .map(p => `<option value="${p}">${p}</option>`)
        .join('');
      
      document.getElementById('batsmanModal').classList.add('active');
      document.getElementById('batsmanModal').removeAttribute('data-mode');
    }

    function confirmNewBatsman() {
      const select = document.getElementById('newBatsmanSelect');
      matchState.striker = select.value;
      closeModal('batsmanModal');
      updateDisplay();
    }

    // Complete over
    function completeOver() {
      matchState.overs += 1;
      matchState.balls = 0;
      matchState.thisOver = [];
      
      // Check if innings over
      if (matchState.overs >= matchState.setup.oversPerInnings) {
        alert('Innings Complete!');
        newInnings();
        return;
      }
      
      // Auto swap strike
      swapStrike();
      
      // Prompt for new bowler
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
      matchState.bowler = select.value;
      
      // Initialize bowler if needed
      if (!matchState.bowlers[matchState.bowler]) {
        matchState.bowlers[matchState.bowler] = { overs: 0, balls: 0, runs: 0, wickets: 0 };
      }
      
      closeModal('newOverModal');
      updateDisplay();
    }

    // Swap strike
    function swapStrike() {
      haptic('light');
      [matchState.striker, matchState.nonStriker] = [matchState.nonStriker, matchState.striker];
      updateDisplay();
    }

    // Undo last ball
    function undoLastBall() {
      if (matchState.ballHistory.length === 0) return;
      
      haptic('medium');
      
      const lastBall = matchState.ballHistory.pop();
      
      // Reverse the changes based on ball type
      if (lastBall.type === 'legal') {
        matchState.batsmen[lastBall.striker].runs -= lastBall.runs;
        matchState.batsmen[lastBall.striker].balls -= 1;
        if (lastBall.runs === 4) matchState.batsmen[lastBall.striker].fours -= 1;
        if (lastBall.runs === 6) matchState.batsmen[lastBall.striker].sixes -= 1;
        matchState.bowlers[lastBall.bowler].runs -= lastBall.runs;
        matchState.bowlers[lastBall.bowler].balls -= 1;
        matchState.score.runs -= lastBall.runs;
        matchState.balls -= 1;
      } else if (lastBall.type === 'noball') {
        matchState.batsmen[lastBall.striker].runs -= lastBall.runs;
        if (lastBall.runs === 4) matchState.batsmen[lastBall.striker].fours -= 1;
        if (lastBall.runs === 6) matchState.batsmen[lastBall.striker].sixes -= 1;
        matchState.bowlers[lastBall.bowler].runs -= (1 + lastBall.runs);
        matchState.score.runs -= (1 + lastBall.runs);
        matchState.extras.nb -= 1;
      } else if (lastBall.type === 'wide') {
        matchState.bowlers[lastBall.bowler].runs -= lastBall.runs;
        matchState.score.runs -= lastBall.runs;
        matchState.extras.wd -= lastBall.runs;
      } else if (lastBall.type === 'wicket') {
        matchState.batsmen[lastBall.striker].out = false;
        matchState.bowlers[lastBall.bowler].wickets -= 1;
        matchState.bowlers[lastBall.bowler].balls -= 1;
        matchState.score.wickets -= 1;
        matchState.balls -= 1;
      }
      
      matchState.thisOver.pop();
      updateDisplay();
    }

    // Update display
    function updateDisplay() {
      // Main score
      document.getElementById('mainScore').textContent = 
        `${matchState.score.runs}/${matchState.score.wickets}`;
      
      // Overs
      const oversDisplay = `${matchState.overs}.${matchState.balls}`;
      document.getElementById('oversDisplay').textContent = oversDisplay;
      
      // Run rate
      const totalOvers = matchState.overs + (matchState.balls / 6);
      const runRate = totalOvers > 0 ? (matchState.score.runs / totalOvers).toFixed(2) : '0.00';
      document.getElementById('runRate').textContent = runRate;
      
      // Team name
      document.getElementById('teamName').textContent = 
        matchState.setup[matchState.battingTeam].name;
      
      // Target display (2nd innings)
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
      
      // Free hit badge
      document.getElementById('freeHitBadge').style.display = 
        matchState.freeHit ? 'inline-block' : 'none';
      
      // Current players
      if (matchState.striker) {
        const strikerData = matchState.batsmen[matchState.striker];
        document.getElementById('striker').textContent = matchState.striker;
        document.getElementById('strikerStats').textContent = 
          `${strikerData.runs}(${strikerData.balls})`;
      }
      
      if (matchState.nonStriker) {
        const nonStrikerData = matchState.batsmen[matchState.nonStriker];
        document.getElementById('nonStriker').textContent = matchState.nonStriker;
        document.getElementById('nonStrikerStats').textContent = 
          `${nonStrikerData.runs}(${nonStrikerData.balls})`;
      }
      
      if (matchState.bowler) {
        const bowlerData = matchState.bowlers[matchState.bowler];
        const bowlerOvers = `${Math.floor(bowlerData.balls / 6)}.${bowlerData.balls % 6}`;
        document.getElementById('bowler').textContent = matchState.bowler;
        document.getElementById('bowlerStats').textContent = 
          `${bowlerData.wickets}-${bowlerData.runs} (${bowlerOvers})`;
      }
      
      // This over
      const thisOverEl = document.getElementById('thisOver');
      thisOverEl.innerHTML = matchState.thisOver.map(ball => {
        let className = '';
        if (ball === 'W') className = 'wicket';
        else if (ball === '4' || ball === '6') className = 'boundary';
        return `<div class="ball-badge ${className}">${ball}</div>`;
      }).join('');
      
      // Update stats tables
      updateStatsTable();
    }

    function updateStatsTable() {
      // Batting stats
      const battingStatsEl = document.getElementById('battingStats');
      const battingPlayers = matchState.setup[matchState.battingTeam].players;
      
      battingStatsEl.innerHTML = battingPlayers
        .map(p => {
          const stats = matchState.batsmen[p];
          if (!stats) return '';
          const sr = stats.balls > 0 ? ((stats.runs / stats.balls) * 100).toFixed(2) : '0.00';
          const status = stats.out ? ' *' : (p === matchState.striker || p === matchState.nonStriker) ? ' (batting)' : '';
          return `
            <tr>
              <td>${p}${status}</td>
              <td>${stats.runs}</td>
              <td>${stats.balls}</td>
              <td>${stats.fours}</td>
              <td>${stats.sixes}</td>
              <td>${sr}</td>
            </tr>
          `;
        }).join('');
      
      // Bowling stats
      const bowlingStatsEl = document.getElementById('bowlingStats');
      const bowlingPlayers = matchState.setup[matchState.bowlingTeam].players;
      
      bowlingStatsEl.innerHTML = bowlingPlayers
        .filter(p => matchState.bowlers[p] && matchState.bowlers[p].balls > 0)
        .map(p => {
          const stats = matchState.bowlers[p];
          const overs = `${Math.floor(stats.balls / 6)}.${stats.balls % 6}`;
          const totalOvers = stats.balls / 6;
          const econ = totalOvers > 0 ? (stats.runs / totalOvers).toFixed(2) : '0.00';
          return `
            <tr>
              <td>${p}</td>
              <td>${overs}</td>
              <td>${stats.runs}</td>
              <td>${stats.wickets}</td>
              <td>${econ}</td>
            </tr>
          `;
        }).join('');
      
      // Extras
      document.getElementById('nbCount').textContent = matchState.extras.nb;
      document.getElementById('wdCount').textContent = matchState.extras.wd;
      document.getElementById('bCount').textContent = matchState.extras.b;
      document.getElementById('lbCount').textContent = matchState.extras.lb;
    }

    function updateSettings() {
      document.getElementById('settingsFormat').textContent = matchState.setup.matchFormat;
      document.getElementById('settingsOvers').textContent = matchState.setup.oversPerInnings;
      document.getElementById('settingsWickets').textContent = matchState.setup.wicketsLimit;
      document.getElementById('settingsTeamA').textContent = matchState.setup.teamA.name;
      document.getElementById('settingsTeamB').textContent = matchState.setup.teamB.name;
    }

    // Tab switching
    function showTab(tab) {
      haptic('light');
      document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      
      document.querySelector(`[onclick="showTab('${tab}')"]`).classList.add('active');
      document.getElementById(`${tab}Tab`).classList.add('active');
    }

    // Modal helpers
    function closeModal(modalId) {
      document.getElementById(modalId).classList.remove('active');
    }

    // New innings
    function newInnings() {
      if (matchState.innings === 2) {
        alert('Match Complete!');
        return;
      }
      
      // Save first innings score
      matchState.firstInningsScore = matchState.score.runs;
      
      matchState.innings = 2;
      [matchState.battingTeam, matchState.bowlingTeam] = [matchState.bowlingTeam, matchState.battingTeam];
      matchState.score = { runs: 0, wickets: 0 };
      matchState.overs = 0;
      matchState.balls = 0;
      matchState.thisOver = [];
      matchState.ballHistory = [];
      matchState.freeHit = false;
      
      // Don't reset extras - keep cumulative for the match
      // But if you want per-innings extras, uncomment this:
      // matchState.extras = { nb: 0, wd: 0, b: 0, lb: 0 };
      
      // Clear current players to force selection
      matchState.striker = null;
      matchState.nonStriker = null;
      matchState.bowler = null;
      
      updateDisplay();
      
      // Show target alert
      const target = matchState.firstInningsScore + 1;
      alert(`Innings break!\n\n${matchState.setup[matchState.bowlingTeam].name} scored ${matchState.firstInningsScore} runs.\n\n${matchState.setup[matchState.battingTeam].name} needs ${target} runs to win!`);
      
      // Prompt for new opening players
      promptSecondInningsPlayers();
    }
    
    function promptSecondInningsPlayers() {
      // Show modal for selecting opening batsmen
      const bat1Select = document.getElementById('newBatsmanSelect');
      const battingPlayers = matchState.setup[matchState.battingTeam].players;
      
      bat1Select.innerHTML = battingPlayers
        .filter(p => !matchState.batsmen[p].out)
        .map(p => `<option value="${p}">${p}</option>`)
        .join('');
      
      document.getElementById('batsmanModal').classList.add('active');
      
      // Override the confirm button behavior for second innings
      document.getElementById('batsmanModal').setAttribute('data-mode', 'second-innings-bat1');
    }
    
    function confirmNewBatsman() {
      const select = document.getElementById('newBatsmanSelect');
      const mode = document.getElementById('batsmanModal').getAttribute('data-mode');
      
      if (mode === 'second-innings-bat1') {
        // First opening batsman selected
        matchState.striker = select.value;
        
        // Now ask for second batsman
        const battingPlayers = matchState.setup[matchState.battingTeam].players;
        select.innerHTML = battingPlayers
          .filter(p => !matchState.batsmen[p].out && p !== matchState.striker)
          .map(p => `<option value="${p}">${p}</option>`)
          .join('');
        
        document.getElementById('batsmanModal').setAttribute('data-mode', 'second-innings-bat2');
        
      } else if (mode === 'second-innings-bat2') {
        // Second opening batsman selected
        matchState.nonStriker = select.value;
        closeModal('batsmanModal');
        
        // Now ask for opening bowler
        const bowlerSelect = document.getElementById('newBowlerSelect');
        const bowlingPlayers = matchState.setup[matchState.bowlingTeam].players;
        
        bowlerSelect.innerHTML = bowlingPlayers
          .map(p => `<option value="${p}">${p}</option>`)
          .join('');
        
        document.getElementById('newOverModal').classList.add('active');
        document.getElementById('newOverModal').setAttribute('data-mode', 'second-innings');
        
      } else {
        // Regular new batsman (after wicket)
        matchState.striker = select.value;
        closeModal('batsmanModal');
        updateDisplay();
      }
    }
    
    function confirmNewOver() {
      const select = document.getElementById('newBowlerSelect');
      const mode = document.getElementById('newOverModal').getAttribute('data-mode');
      
      matchState.bowler = select.value;
      
      // Initialize bowler if needed
      if (!matchState.bowlers[matchState.bowler]) {
        matchState.bowlers[matchState.bowler] = { overs: 0, balls: 0, runs: 0, wickets: 0 };
      }
      
      closeModal('newOverModal');
      document.getElementById('newOverModal').removeAttribute('data-mode');
      updateDisplay();
    }

    function endMatch() {
      if (confirm('End the match?')) {
        alert('Match ended. Thanks for using StumpVision!');
      }
    }

    function resetMatch() {
      if (confirm('Reset the entire match? This cannot be undone.')) {
        localStorage.removeItem('stumpvision_match');
        window.location.href = 'setup.php';
      }
    }

    // Initialize on load
    init();
  </script>
</body>
</html>">
      <div class="stats-title">Batting Stats</div>
      <table>
        <thead>
          <tr>
            <th>Player</th>
            <th>R</th>
            <th>B</th>
            <th>4s</th>
            <th>6s</th>
            <th>SR</th>
          </tr>
        </thead>
        <tbody id="battingStats"></tbody>
      </table>
    </div>

    <div class="stats-table