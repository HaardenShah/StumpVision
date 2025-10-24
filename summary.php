<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <meta name="theme-color" content="#ffffff">
  <link rel="manifest" href="manifest.webmanifest">
  <link rel="apple-touch-icon" href="assets/icons/icon-192.png">
  <title>Match Summary - StumpVision</title>
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
      padding-bottom: 40px;
    }
    .container { max-width: 800px; margin: 0 auto; padding: 20px; }
    .header { 
      background: linear-gradient(135deg, var(--accent-light), var(--card));
      padding: 32px 20px;
      text-align: center;
      margin-bottom: 24px;
      border-radius: 16px;
      border: 2px solid var(--line);
    }
    .match-result { 
      font-size: 28px; 
      font-weight: 900; 
      margin-bottom: 12px;
      color: var(--accent);
    }
    .match-title { 
      font-size: 20px; 
      font-weight: 700; 
      margin-bottom: 16px;
    }
    .score-summary {
      display: grid;
      grid-template-columns: 1fr auto 1fr;
      gap: 16px;
      align-items: center;
      margin-top: 20px;
    }
    .team-score {
      text-align: center;
      padding: 20px;
      background: var(--card);
      border: 2px solid var(--line);
      border-radius: 12px;
    }
    .team-name {
      font-size: 16px;
      font-weight: 700;
      margin-bottom: 8px;
      color: var(--muted);
    }
    .score {
      font-size: 36px;
      font-weight: 900;
      color: var(--ink);
    }
    .overs {
      font-size: 14px;
      color: var(--muted);
      margin-top: 4px;
    }
    .vs-divider {
      font-size: 20px;
      font-weight: 700;
      color: var(--muted);
    }
    .winner-badge {
      display: inline-block;
      background: var(--success);
      color: white;
      padding: 4px 12px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 700;
      margin-top: 8px;
    }
    .stats-section {
      background: var(--card);
      border: 2px solid var(--line);
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 20px;
    }
    .section-title {
      font-size: 18px;
      font-weight: 700;
      margin-bottom: 16px;
      color: var(--accent);
      border-bottom: 2px solid var(--line);
      padding-bottom: 8px;
    }
    .innings-header {
      font-size: 16px;
      font-weight: 700;
      margin-bottom: 12px;
      color: var(--ink);
    }
    table { 
      width: 100%; 
      border-collapse: collapse;
      margin-bottom: 20px;
    }
    th, td { 
      padding: 10px 8px; 
      text-align: left; 
      font-size: 14px; 
    }
    th { 
      font-weight: 700; 
      color: var(--muted); 
      font-size: 12px; 
      text-transform: uppercase; 
      border-bottom: 2px solid var(--line); 
    }
    td { 
      border-bottom: 1px solid var(--line); 
    }
    tr:last-child td { 
      border-bottom: none; 
    }
    .highlight-row {
      background: var(--accent-light);
    }
    .action-buttons {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin-top: 24px;
    }
    .btn {
      padding: 16px;
      border: none;
      border-radius: 12px;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      text-align: center;
      text-decoration: none;
      display: block;
    }
    .btn-primary {
      background: var(--accent);
      color: white;
    }
    .btn-secondary {
      background: var(--card);
      color: var(--ink);
      border: 2px solid var(--line);
    }
    .btn-success {
      background: var(--success);
      color: white;
    }
    .loading {
      text-align: center;
      padding: 40px;
      color: var(--muted);
    }
    .error {
      background: var(--danger-light);
      color: var(--danger);
      padding: 16px;
      border-radius: 12px;
      margin: 20px;
      border: 2px solid var(--danger);
    }
    .extras-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 12px;
      margin-top: 12px;
    }
    .extra-item {
      display: flex;
      justify-content: space-between;
      padding: 8px 12px;
      background: var(--bg);
      border-radius: 8px;
    }
    @media (max-width: 640px) {
      .score-summary {
        grid-template-columns: 1fr;
      }
      .vs-divider {
        display: none;
      }
      .action-buttons {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div id="loading" class="loading">
      <h2>Loading match summary...</h2>
    </div>

    <div id="error" class="error" style="display: none;"></div>

    <div id="summary" style="display: none;">
      <!-- Match Result Header -->
      <div class="header">
        <div class="match-result" id="matchResult">Match Complete</div>
        <div class="match-title" id="matchTitle">Team A vs Team B</div>
        
        <div class="score-summary">
          <div class="team-score" id="team1Score">
            <div class="team-name" id="team1Name">Team A</div>
            <div class="score" id="team1Runs">0/0</div>
            <div class="overs" id="team1Overs">(0.0 overs)</div>
            <div id="team1Winner" class="winner-badge" style="display: none;">WINNER 🏆</div>
          </div>
          
          <div class="vs-divider">vs</div>
          
          <div class="team-score" id="team2Score">
            <div class="team-name" id="team2Name">Team B</div>
            <div class="score" id="team2Runs">0/0</div>
            <div class="overs" id="team2Overs">(0.0 overs)</div>
            <div id="team2Winner" class="winner-badge" style="display: none;">WINNER 🏆</div>
          </div>
        </div>
      </div>

      <!-- Innings 1 Stats -->
      <div class="stats-section">
        <div class="section-title">First Innings</div>
        <div class="innings-header" id="innings1Team">Team A Batting</div>
        
        <table>
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
          <tbody id="innings1Batting"></tbody>
        </table>

        <div class="innings-header" id="innings1BowlingTeam">Team B Bowling</div>
        <table>
          <thead>
            <tr>
              <th>Bowler</th>
              <th>O</th>
              <th>R</th>
              <th>W</th>
              <th>Econ</th>
            </tr>
          </thead>
          <tbody id="innings1Bowling"></tbody>
        </table>

        <div class="extras-grid" id="innings1Extras"></div>
      </div>

      <!-- Innings 2 Stats -->
      <div class="stats-section" id="innings2Section" style="display: none;">
        <div class="section-title">Second Innings</div>
        <div class="innings-header" id="innings2Team">Team B Batting</div>
        
        <table>
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
          <tbody id="innings2Batting"></tbody>
        </table>

        <div class="innings-header" id="innings2BowlingTeam">Team A Bowling</div>
        <table>
          <thead>
            <tr>
              <th>Bowler</th>
              <th>O</th>
              <th>R</th>
              <th>W</th>
              <th>Econ</th>
            </tr>
          </thead>
          <tbody id="innings2Bowling"></tbody>
        </table>

        <div class="extras-grid" id="innings2Extras"></div>
      </div>

      <!-- Action Buttons -->
      <div class="action-buttons">
        <button class="btn btn-success" onclick="shareMatch()">📤 Share Score Card</button>
        <a href="setup.php" class="btn btn-secondary">🏏 New Match</a>
        <a href="index.php" class="btn btn-secondary">↩️ Back to Scorer</a>
        <button class="btn btn-primary" onclick="viewAllMatches()">📊 View History</button>
      </div>
    </div>
  </div>

  <script>
    let matchData = null;
    let matchId = null;

    async function loadMatchData() {
      try {
        // Get match data from localStorage
        const savedMatch = localStorage.getItem('stumpvision_completed_match');
        
        if (!savedMatch) {
          showError('No match data found. Please complete a match first.');
          return;
        }

        matchData = JSON.parse(savedMatch);
        matchId = matchData.saveId;

        // Display the summary
        displayMatchSummary();
        
        document.getElementById('loading').style.display = 'none';
        document.getElementById('summary').style.display = 'block';

      } catch (err) {
        showError('Error loading match: ' + err.message);
      }
    }

    function displayMatchSummary() {
      const { meta, teams, innings } = matchData.payload;

      // Match title
      document.getElementById('matchTitle').textContent = meta.title;

      // Determine winner and display result
      const inn1 = innings[0];
      const inn2 = innings[1];
      
      let resultText = 'Match Complete';
      let winnerTeam = null;

      if (inn2) {
        const team1Runs = inn1.runs;
        const team2Runs = inn2.runs;

        if (team2Runs > team1Runs) {
          const wicketsLeft = meta.wicketsLimit || 10 - inn2.wickets;
          resultText = `${teams[inn2.batting].name} won by ${wicketsLeft} wickets`;
          winnerTeam = inn2.batting;
        } else if (team1Runs > team2Runs) {
          const margin = team1Runs - team2Runs;
          resultText = `${teams[inn1.batting].name} won by ${margin} runs`;
          winnerTeam = inn1.batting;
        } else {
          resultText = 'Match Tied';
        }
      } else {
        resultText = 'First Innings Complete';
      }

      document.getElementById('matchResult').textContent = resultText;

      // Display scores
      displayTeamScore(teams[inn1.batting], inn1, 'team1', winnerTeam === inn1.batting);
      
      if (inn2) {
        displayTeamScore(teams[inn2.batting], inn2, 'team2', winnerTeam === inn2.batting);
      } else {
        displayTeamScore(teams[1 - inn1.batting], { runs: 0, wickets: 0, balls: 0 }, 'team2', false);
      }

      // Display innings stats
      displayInnings(1, inn1, teams);
      
      if (inn2) {
        document.getElementById('innings2Section').style.display = 'block';
        displayInnings(2, inn2, teams);
      }
    }

    function displayTeamScore(team, innings, prefix, isWinner) {
      const overs = Math.floor(innings.balls / 6) + '.' + (innings.balls % 6);
      
      document.getElementById(`${prefix}Name`).textContent = team.name;
      document.getElementById(`${prefix}Runs`).textContent = `${innings.runs}/${innings.wickets}`;
      document.getElementById(`${prefix}Overs`).textContent = `(${overs} overs)`;
      
      if (isWinner) {
        document.getElementById(`${prefix}Winner`).style.display = 'inline-block';
      }
    }

    function displayInnings(inningsNum, innings, teams) {
      const prefix = `innings${inningsNum}`;
      const battingTeam = teams[innings.batting];
      const bowlingTeam = teams[innings.bowling];

      // Team names
      document.getElementById(`${prefix}Team`).textContent = `${battingTeam.name} Batting`;
      document.getElementById(`${prefix}BowlingTeam`).textContent = `${bowlingTeam.name} Bowling`;

      // Batting stats
      const battingEl = document.getElementById(`${prefix}Batting`);
      battingEl.innerHTML = (innings.batStats || []).map(b => {
        const sr = b.balls > 0 ? ((b.runs / b.balls) * 100).toFixed(1) : '0.0';
        const status = b.out ? '*' : 'not out';
        const rowClass = b.runs >= 50 ? 'highlight-row' : '';
        return `
          <tr class="${rowClass}">
            <td>${b.name} ${b.out ? '' : '<small>(not out)</small>'}</td>
            <td><strong>${b.runs}</strong></td>
            <td>${b.balls}</td>
            <td>${b.fours}</td>
            <td>${b.sixes}</td>
            <td>${sr}</td>
          </tr>
        `;
      }).join('') || '<tr><td colspan="6">No batting data</td></tr>';

      // Bowling stats
      const bowlingEl = document.getElementById(`${prefix}Bowling`);
      bowlingEl.innerHTML = (innings.bowlStats || []).map(b => {
        const overs = Math.floor(b.balls / 6) + '.' + (b.balls % 6);
        const totalOvers = b.balls / 6;
        const econ = totalOvers > 0 ? (b.runs / totalOvers).toFixed(2) : '0.00';
        const rowClass = b.wickets >= 3 ? 'highlight-row' : '';
        return `
          <tr class="${rowClass}">
            <td>${b.name}</td>
            <td>${overs}</td>
            <td>${b.runs}</td>
            <td><strong>${b.wickets}</strong></td>
            <td>${econ}</td>
          </tr>
        `;
      }).join('') || '<tr><td colspan="5">No bowling data</td></tr>';

      // Extras
      const extras = innings.extras || { nb: 0, wd: 0, b: 0, lb: 0 };
      const total = extras.nb + extras.wd + extras.b + extras.lb;
      document.getElementById(`${prefix}Extras`).innerHTML = `
        <div class="extra-item"><span>No Balls</span><strong>${extras.nb}</strong></div>
        <div class="extra-item"><span>Wides</span><strong>${extras.wd}</strong></div>
        <div class="extra-item"><span>Byes</span><strong>${extras.b}</strong></div>
        <div class="extra-item"><span>Leg Byes</span><strong>${extras.lb}</strong></div>
        <div class="extra-item" style="grid-column: 1 / -1;"><span><strong>Total Extras</strong></span><strong>${total}</strong></div>
      `;
    }

    function showError(message) {
      document.getElementById('loading').style.display = 'none';
      document.getElementById('error').textContent = message;
      document.getElementById('error').style.display = 'block';
      
      // Show back button
      const container = document.querySelector('.container');
      container.innerHTML += '<div class="action-buttons" style="margin-top: 20px;"><a href="index.php" class="btn btn-secondary">↩️ Back to Scorer</a><a href="setup.php" class="btn btn-primary">🏏 New Match</a></div>';
    }

    async function shareMatch() {
      if (!matchId) {
        alert('Match not saved yet. Please try again.');
        return;
      }

      try {
        const response = await fetch(`api/renderCard.php?id=${encodeURIComponent(matchId)}`);
        const result = await response.json();
        
        if (!result.ok) {
          alert('Could not generate share card: ' + (result.error || 'Unknown error'));
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
              title: matchData.payload.meta.title,
              text: 'Match scorecard from StumpVision',
              files: [file]
            });
            return;
          } catch (shareErr) {
            console.log('Share cancelled or failed');
          }
        }
        
        // Fallback: download
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = file.name;
        a.click();
      } catch (err) {
        alert('Share error: ' + err.message);
      }
    }

    function viewAllMatches() {
      // TODO: Implement match history page
      alert('Match history feature coming soon!');
    }

    // Load match on page load
    loadMatchData();
  </script>
</body>
</html>