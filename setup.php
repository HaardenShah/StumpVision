<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <meta name="theme-color" content="#ffffff">
  <meta name="description" content="Set up your cricket match">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <link rel="manifest" href="manifest.webmanifest">
  <link rel="apple-touch-icon" href="assets/icons/icon-192.png">
  <title>StumpVision - Match Setup</title>
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
      padding-bottom: 40px;
    }

    .container {
      max-width: 600px;
      margin: 0 auto;
      padding: 20px;
    }

    .header {
      text-align: center;
      margin-bottom: 32px;
      padding-top: 20px;
    }

    .header h1 {
      font-size: 28px;
      font-weight: 800;
      margin-bottom: 8px;
    }

    .header p {
      color: var(--muted);
      font-size: 14px;
    }

    .card {
      background: var(--card);
      border: 2px solid var(--line);
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 2px 8px var(--shadow);
    }

    .card-title {
      font-weight: 700;
      font-size: 16px;
      margin-bottom: 16px;
      color: var(--ink);
    }

    .form-group {
      margin-bottom: 16px;
    }

    .form-group label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: var(--muted);
      margin-bottom: 6px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    input, select {
      width: 100%;
      padding: 12px 14px;
      border: 2px solid var(--line);
      border-radius: 12px;
      background: var(--bg);
      color: var(--ink);
      font-size: 15px;
      transition: all 0.2s;
    }

    input:focus, select:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 3px var(--accent-light);
    }

    .players-input {
      margin-bottom: 12px;
    }

    .player-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 8px;
      min-height: 40px;
    }

    .player-tag {
      background: var(--accent-light);
      border: 2px solid var(--accent);
      color: var(--ink);
      padding: 6px 12px;
      border-radius: 999px;
      font-size: 14px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
      animation: fadeIn 0.2s;
    }

    .player-tag button {
      background: none;
      border: none;
      color: var(--danger);
      font-weight: 800;
      cursor: pointer;
      padding: 0 4px;
      font-size: 16px;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: scale(0.8); }
      to { opacity: 1; transform: scale(1); }
    }

    .btn {
      width: 100%;
      padding: 16px;
      border: none;
      border-radius: 12px;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s;
      margin-top: 8px;
    }

    .btn-primary {
      background: var(--accent);
      color: white;
      box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(14, 165, 233, 0.4);
    }

    .btn-primary:active {
      transform: translateY(0);
    }

    .btn-secondary {
      background: var(--card);
      color: var(--ink);
      border: 2px solid var(--line);
    }

    .grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }

    .hint {
      font-size: 13px;
      color: var(--muted);
      margin-top: 8px;
    }

    .error {
      background: #fee2e2;
      color: #dc2626;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 16px;
      font-size: 14px;
      border: 2px solid #fca5a5;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>StumpVision</h1>
      <p>Set up your match</p>
      <p style="font-size: 12px; color: var(--muted); margin-top: 4px;">Tip: Enable vibration in your phone settings for haptic feedback</p>
    </div>

    <div id="errorMsg" class="error" style="display: none;"></div>

    <div class="card">
      <div class="card-title">Match Details</div>
      <div class="form-group">
        <label>Match Format</label>
        <select id="matchFormat">
          <option value="limited">Limited Overs</option>
          <option value="test">Test Match</option>
        </select>
      </div>
      <div class="grid-2">
        <div class="form-group">
          <label>Overs per Innings</label>
          <input type="number" id="oversPerInnings" value="20" min="1" max="50">
        </div>
        <div class="form-group">
          <label>Wickets Limit</label>
          <input type="number" id="wicketsLimit" value="10" min="1" max="11">
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-title">Toss</div>
      <div class="form-group">
        <label>Who won the toss?</label>
        <select id="tossWinner">
          <option value="teamA">Team A</option>
          <option value="teamB">Team B</option>
        </select>
      </div>
      <div class="form-group">
        <label>Elected to</label>
        <select id="tossDecision">
          <option value="bat">Bat First</option>
          <option value="bowl">Bowl First</option>
        </select>
      </div>
    </div>

    <div class="card">
      <div class="card-title">Team A</div>
      <div class="form-group">
        <label>Team Name</label>
        <input type="text" id="teamAName" placeholder="Enter team name" value="Team A">
      </div>
      <div class="form-group">
        <label>Players</label>
        <input type="text" id="teamAPlayerInput" placeholder="Type name and press Enter" class="players-input">
        <div class="player-tags" id="teamAPlayers"></div>
        <p class="hint">Press Enter to add each player</p>
      </div>
    </div>

    <div class="card">
      <div class="card-title">Team B</div>
      <div class="form-group">
        <label>Team Name</label>
        <input type="text" id="teamBName" placeholder="Enter team name" value="Team B">
      </div>
      <div class="form-group">
        <label>Players</label>
        <input type="text" id="teamBPlayerInput" placeholder="Type name and press Enter" class="players-input">
        <div class="player-tags" id="teamBPlayers"></div>
        <p class="hint">Press Enter to add each player</p>
      </div>
    </div>

    <div class="card">
      <div class="card-title">Opening Players</div>
      <div class="form-group">
        <label>Opening Batsman 1</label>
        <select id="openingBat1">
          <option value="">Select player...</option>
        </select>
      </div>
      <div class="form-group">
        <label>Opening Batsman 2</label>
        <select id="openingBat2">
          <option value="">Select player...</option>
        </select>
      </div>
      <div class="form-group">
        <label>Opening Bowler</label>
        <select id="openingBowler">
          <option value="">Select player...</option>
        </select>
      </div>
      <p class="hint">Add players to teams first, then select openers</p>
    </div>

    <button class="btn btn-primary" id="startMatch">Start Match</button>
  </div>

  <script>
    // State management
    const state = {
      teamA: { name: 'Team A', players: [] },
      teamB: { name: 'Team B', players: [] },
      matchFormat: 'limited',
      oversPerInnings: 20,
      wicketsLimit: 10,
      tossWinner: 'teamA',
      tossDecision: 'bat',
      openingBat1: null,
      openingBat2: null,
      openingBowler: null
    };

    // Player management
    function addPlayer(team, name) {
      if (!name.trim()) return;
      if (state[team].players.includes(name.trim())) return;
      
      state[team].players.push(name.trim());
      renderPlayers(team);
    }

    function removePlayer(team, name) {
      state[team].players = state[team].players.filter(p => p !== name);
      renderPlayers(team);
    }

    function renderPlayers(team) {
      const container = document.getElementById(`${team}Players`);
      container.innerHTML = state[team].players.map(player => `
        <div class="player-tag">
          <span>${player}</span>
          <button onclick="removePlayer('${team}', '${player}')">×</button>
        </div>
      `).join('');
      
      // Update opening player dropdowns
      updateOpeningSelects();
    }
    
    function updateOpeningSelects() {
      // Determine batting and bowling teams based on toss
      const battingTeam = state.tossDecision === 'bat' ? state.tossWinner : 
                          (state.tossWinner === 'teamA' ? 'teamB' : 'teamA');
      const bowlingTeam = state.tossDecision === 'bat' ? 
                          (state.tossWinner === 'teamA' ? 'teamB' : 'teamA') : 
                          state.tossWinner;
      
      // Update batsman selects
      const bat1Select = document.getElementById('openingBat1');
      const bat2Select = document.getElementById('openingBat2');
      const battingPlayers = state[battingTeam].players;
      
      const batOptions = '<option value="">Select player...</option>' + 
        battingPlayers.map(p => `<option value="${p}">${p}</option>`).join('');
      
      bat1Select.innerHTML = batOptions;
      bat2Select.innerHTML = batOptions;
      
      // Update bowler select
      const bowlerSelect = document.getElementById('openingBowler');
      const bowlingPlayers = state[bowlingTeam].players;
      
      bowlerSelect.innerHTML = '<option value="">Select player...</option>' + 
        bowlingPlayers.map(p => `<option value="${p}">${p}</option>`).join('');
      
      // Restore previous selections if valid
      if (battingPlayers.includes(state.openingBat1)) {
        bat1Select.value = state.openingBat1;
      }
      if (battingPlayers.includes(state.openingBat2)) {
        bat2Select.value = state.openingBat2;
      }
      if (bowlingPlayers.includes(state.openingBowler)) {
        bowlerSelect.value = state.openingBowler;
      }
    }

    // Event listeners
    const teamAInput = document.getElementById('teamAPlayerInput');
    const teamBInput = document.getElementById('teamBPlayerInput');
    
    // Handle both Enter key and mobile "Next" button
    teamAInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        addPlayer('teamA', e.target.value);
        e.target.value = '';
        e.target.focus();
      }
    });
    
    teamAInput.addEventListener('blur', (e) => {
      if (e.target.value.trim()) {
        addPlayer('teamA', e.target.value);
        e.target.value = '';
      }
    });

    teamBInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        addPlayer('teamB', e.target.value);
        e.target.value = '';
        e.target.focus();
      }
    });
    
    teamBInput.addEventListener('blur', (e) => {
      if (e.target.value.trim()) {
        addPlayer('teamB', e.target.value);
        e.target.value = '';
      }
    });

    document.getElementById('teamAName').addEventListener('input', (e) => {
      state.teamA.name = e.target.value;
    });

    document.getElementById('teamBName').addEventListener('input', (e) => {
      state.teamB.name = e.target.value;
    });

    document.getElementById('oversPerInnings').addEventListener('input', (e) => {
      state.oversPerInnings = parseInt(e.target.value);
    });

    document.getElementById('wicketsLimit').addEventListener('input', (e) => {
      state.wicketsLimit = parseInt(e.target.value);
    });

    document.getElementById('matchFormat').addEventListener('change', (e) => {
      state.matchFormat = e.target.value;
    });
    
    document.getElementById('tossWinner').addEventListener('change', (e) => {
      state.tossWinner = e.target.value;
      updateOpeningSelects();
    });
    
    document.getElementById('tossDecision').addEventListener('change', (e) => {
      state.tossDecision = e.target.value;
      updateOpeningSelects();
    });
    
    document.getElementById('openingBat1').addEventListener('change', (e) => {
      state.openingBat1 = e.target.value;
    });
    
    document.getElementById('openingBat2').addEventListener('change', (e) => {
      state.openingBat2 = e.target.value;
    });
    
    document.getElementById('openingBowler').addEventListener('change', (e) => {
      state.openingBowler = e.target.value;
    });

    // Start match
    document.getElementById('startMatch').addEventListener('click', () => {
      const errorMsg = document.getElementById('errorMsg');
      
      // Validation
      if (!state.teamA.name.trim() || !state.teamB.name.trim()) {
        errorMsg.textContent = 'Please enter names for both teams';
        errorMsg.style.display = 'block';
        return;
      }

      if (state.teamA.players.length < 2) {
        errorMsg.textContent = 'Team A needs at least 2 players';
        errorMsg.style.display = 'block';
        return;
      }

      if (state.teamB.players.length < 2) {
        errorMsg.textContent = 'Team B needs at least 2 players';
        errorMsg.style.display = 'block';
        return;
      }
      
      if (!state.openingBat1 || !state.openingBat2) {
        errorMsg.textContent = 'Please select both opening batsmen';
        errorMsg.style.display = 'block';
        return;
      }
      
      if (state.openingBat1 === state.openingBat2) {
        errorMsg.textContent = 'Opening batsmen must be different players';
        errorMsg.style.display = 'block';
        return;
      }
      
      if (!state.openingBowler) {
        errorMsg.textContent = 'Please select an opening bowler';
        errorMsg.style.display = 'block';
        return;
      }

      // Save to localStorage
      localStorage.setItem('stumpvision_match', JSON.stringify(state));
      
      // Navigate to scoring page
      window.location.href = 'index.php';
    });

    // Load from localStorage if exists
    const saved = localStorage.getItem('stumpvision_match');
    if (saved) {
      const loaded = JSON.parse(saved);
      Object.assign(state, loaded);
      
      document.getElementById('teamAName').value = state.teamA.name;
      document.getElementById('teamBName').value = state.teamB.name;
      document.getElementById('oversPerInnings').value = state.oversPerInnings;
      document.getElementById('wicketsLimit').value = state.wicketsLimit;
      document.getElementById('matchFormat').value = state.matchFormat;
      document.getElementById('tossWinner').value = state.tossWinner;
      document.getElementById('tossDecision').value = state.tossDecision;
      
      renderPlayers('teamA');
      renderPlayers('teamB');
      updateOpeningSelects();
      
      if (state.openingBat1) document.getElementById('openingBat1').value = state.openingBat1;
      if (state.openingBat2) document.getElementById('openingBat2').value = state.openingBat2;
      if (state.openingBowler) document.getElementById('openingBowler').value = state.openingBowler;
    }
  </script>
</body>
</html>