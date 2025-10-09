<?php /* index.php — StumpVision (flattened) */ ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover" />
  <meta name="theme-color" content="#0f172a" />
  <title>StumpVision — Pickup Cricket Scorer</title>
  <link rel="manifest" href="manifest.webmanifest">
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="apple-touch-icon" href="assets/icons/icon-192.png">
</head>
<body>
  <div class="wrap">
    <header>
      <h1>🏏 StumpVision</h1>
      <div class="row">
        <button class="btn" id="btnNew">New Match</button>
        <button class="btn" id="btnSave">Save</button>
        <button class="btn" id="btnOpen">Open</button>
        <button class="btn danger" id="btnReset">Reset</button>
      </div>
    </header>

    <main class="grid cols-3">
      <section class="card" id="configCard">
        <div class="row space-between">
          <div class="row wrap">
            <div class="field">
              <label>Title</label>
              <input id="title" type="text" placeholder="e.g., Sunday Pickup at Long's Park">
            </div>
            <div class="field narrow">
              <label>Overs/Side</label>
              <input id="oversPerSide" type="number" min="1" max="50" value="10" inputmode="numeric">
            </div>
            <div class="field narrow">
              <label>Balls/Over</label>
              <input id="ballsPerOver" type="number" min="4" max="10" value="6" inputmode="numeric">
            </div>
            <div class="field">
              <label>Team A</label>
              <input id="teamA" type="text" value="Team A">
            </div>
            <div class="field">
              <label>Team B</label>
              <input id="teamB" type="text" value="Team B">
            </div>
            <div class="field narrow">
              <label>Toss</label>
              <select id="toss"><option value="A">Team A</option><option value="B">Team B</option></select>
            </div>
            <div class="field narrow">
              <label>Opted</label>
              <select id="opted"><option value="bat">Bat</option><option value="bowl">Bowl</option></select>
            </div>
          </div>

          <div class="row stats-pack">
            <div class="stat"><div class="v" id="scoreNow">0/0</div><div class="k">Current</div></div>
            <div class="stat"><div class="v" id="oversNow">0.0</div><div class="k">Overs</div></div>
            <div class="stat"><div class="v" id="rrNow">0.00</div><div class="k">Run Rate</div></div>
          </div>
        </div>

        <div class="card inset">
          <div class="row space-between align-center">
            <div class="row wrap">
              <span class="pill" id="inningsBadge">Innings 1 • <span id="battingTeamLbl">Team A</span> batting</span>
              <span class="pill">Striker: <span id="strikerLbl">—</span></span>
              <span class="pill accent hidden" id="freeHitBadge">FREE HIT</span>
              <span class="pill accent hidden" id="viewOnlyBadge">VIEW ONLY</span>
            </div>
            <div class="row wrap">
              <input id="batter1" type="text" placeholder="Batter 1">
              <input id="batter2" type="text" placeholder="Batter 2">
              <input id="bowler"  type="text" placeholder="Bowler">
              <button class="btn" id="btnSwap">Swap Strike</button>
              <button class="btn" id="btnNewOver">New Over</button>
              <button class="btn" id="btnChangeInnings">Change Innings</button>
            </div>
          </div>

          <div class="pad">
            <div class="padgrid" id="padgrid">
              <button class="btn big" data-ev="dot">·</button>
              <button class="btn big" data-ev="1">1</button>
              <button class="btn big" data-ev="2">2</button>
              <button class="btn big" data-ev="3">3</button>
              <button class="btn big" data-ev="4">4</button>
              <button class="btn big" data-ev="6">6</button>
              <button class="btn big" data-ev="wicket">Wicket</button>
              <button class="btn big" data-ev="undo">Undo</button>
              <button class="btn big" data-ev="wide">Wide</button>
              <button class="btn big accent" data-ev="noball">No Ball</button>
              <button class="btn big" data-ev="bye">Bye</button>
              <button class="btn big" data-ev="legbye">Leg Bye</button>
            </div>
            <div class="overwrap">
              <div class="label">This over</div>
              <div class="overstrip" id="thisOver"></div>
            </div>
          </div>
        </div>
      </section>

      <section class="card">
        <div class="row space-between">
          <div><label>Target</label><div class="v" id="targetBadge">—</div></div>
          <div><label>Required RR</label><div class="v" id="reqRR">—</div></div>
          <div><label>Wickets</label><div class="v" id="wickets">0</div></div>
        </div>
        <hr class="sep">

        <div class="extras">
          <label>Extras</label>
          <div class="extras-row">
            <span class="chip">NB: <b id="x_nb">0</b></span>
            <span class="chip">WD: <b id="x_wd">0</b></span>
            <span class="chip">B: <b id="x_b">0</b></span>
            <span class="chip">LB: <b id="x_lb">0</b></span>
          </div>
        </div>

        <table>
          <thead><tr><th>Team</th><th>Score</th><th>Overs</th></tr></thead>
          <tbody>
            <tr><td id="tAname">Team A</td><td id="tAscore">0/0</td><td id="tAovers">0.0</td></tr>
            <tr><td id="tBname">Team B</td><td id="tBscore">0/0</td><td id="tBovers">0.0</td></tr>
          </tbody>
        </table>
        <div class="row">
          <button class="btn" id="btnExport">Export JSON</button>
          <button class="btn" id="btnCopyShare">Copy Share Link</button>
        </div>
        <p class="hint" id="saveHint"></p>
      </section>

      <section class="card">
        <label>Ball-by-ball Log</label>
        <div id="log" class="log"></div>
      </section>

      <!-- NEW: Stats -->
      <section class="card">
        <label>Batting</label>
        <table id="batStatsTbl">
          <thead><tr><th>Batter</th><th>R</th><th>B</th><th>4s</th><th>6s</th><th>SR</th></tr></thead>
          <tbody id="batStatsBody"></tbody>
        </table>
      </section>

      <section class="card">
        <label>Bowling</label>
        <table id="bowlStatsTbl">
          <thead><tr><th>Bowler</th><th>O</th><th>R</th><th>W</th><th>Eco</th></tr></thead>
          <tbody id="bowlStatsBody"></tbody>
        </table>
      </section>
    </main>

    <footer class="hint">Install StumpVision for offline use — Add to Home Screen.</footer>
  </div>

  <!-- No-Ball chooser -->
  <div id="nbModal" class="modal hidden" role="dialog" aria-modal="true">
    <div class="modal-card">
      <div class="modal-title">No Ball — Runs off the bat?</div>
      <div class="nb-grid">
        <button class="btn nbpick" data-val="0">0</button>
        <button class="btn nbpick" data-val="1">1</button>
        <button class="btn nbpick" data-val="2">2</button>
        <button class="btn nbpick" data-val="3">3</button>
        <button class="btn nbpick" data-val="4">4</button>
        <button class="btn nbpick" data-val="6">6</button>
      </div>
      <div class="row modal-actions">
        <button class="btn danger" id="nbCancel">Cancel</button>
      </div>
      <p class="hint">Total added = 1 (no-ball) + bat runs. Next delivery is a <b>FREE HIT</b>.</p>
    </div>
  </div>

  <!-- Wide chooser -->
  <div id="wdModal" class="modal hidden" role="dialog" aria-modal="true">
    <div class="modal-card">
      <div class="modal-title">Wide — How many runs were run?</div>
      <div class="nb-grid">
        <button class="btn wdpick" data-val="0">0</button>
        <button class="btn wdpick" data-val="1">1</button>
        <button class="btn wdpick" data-val="2">2</button>
        <button class="btn wdpick" data-val="3">3</button>
        <button class="btn wdpick" data-val="4">4</button>
        <button class="btn wdpick" data-val="5">5</button>
      </div>
      <div class="row modal-actions">
        <button class="btn danger" id="wdCancel">Cancel</button>
      </div>
      <p class="hint">Wide adds 1 + runs completed. Ball does not count.</p>
    </div>
  </div>

  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', ()=> navigator.serviceWorker.register('service-worker.js'));
    }
  </script>
  <script type="module" src="assets/js/app.js"></script>
</body>
</html>