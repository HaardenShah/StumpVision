<?php /* index.php — StumpVision (match view with delivery dropdown & picker modal) */ ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover" />
  <meta name="theme-color" content="#0b1120" />
  <title>StumpVision — Match</title>
  <link rel="manifest" href="manifest.webmanifest">
  <link rel="apple-touch-icon" href="assets/icons/icon-192.png">
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body class="has-dock">

<header class="appbar">
  <div class="appbar-row">
    <div class="brand">🏏 StumpVision</div>
    <div class="actions">
      <a class="chip-btn" href="setup.php">Setup</a>
      <button class="chip-btn" id="btnSave">Save</button>
      <button class="chip-btn" id="btnOpen">Open</button>
      <button class="chip-btn" id="btnExport">Export</button>
      <button class="chip-btn accent" id="btnShareRecap">📸 Share Recap</button>
    </div>
  </div>

  <div class="summary">
    <div class="summary-item"><div class="k">Score</div><div class="v" id="scoreNow">0/0</div></div>
    <div class="summary-item"><div class="k">Overs</div><div class="v" id="oversNow">0.0</div></div>
    <div class="summary-item"><div class="k">RR</div><div class="v" id="rrNow">0.00</div></div>
    <div class="summary-item target"><div class="k">Target</div><div class="v" id="targetBadge">—</div></div>
  </div>
</header>

<main class="content">
  <!-- Player strip -->
  <section class="card tight">
    <div class="row wrap gap">
      <span class="pill" id="inningsBadge">Innings 1 • <span id="battingTeamLbl">Team A</span> batting</span>
      <span class="pill">Striker: <span id="strikerLbl">—</span></span>
      <span class="pill accent hidden" id="freeHitBadge">FREE HIT</span>
      <span class="pill accent hidden" id="viewOnlyBadge">VIEW ONLY</span>
    </div>
    <div class="row wrap mt gap">
      <input id="batter1" class="chip-input" placeholder="Striker">
      <input id="batter2" class="chip-input" placeholder="Non-striker">
      <input id="bowler"  class="chip-input" placeholder="Bowler">
      <button class="btn" id="btnSwap">Swap Strike</button>
      <button class="btn" id="btnNewOver">New Over</button>
      <button class="btn" id="btnChangeInnings">Change Innings</button>
    </div>

    <!-- NEW: Ball-by-ball compact dropdown -->
    <div class="row mt gap compact">
      <label class="field sm">
        <span>Ball</span>
        <select id="deliveryPicker"></select>
      </label>
      <button class="btn" id="applyDelivery">Apply</button>
    </div>

    <div class="overwrap mt">
      <div class="label">This over</div>
      <div class="overstrip" id="thisOver"></div>
    </div>
    <p class="hint" id="saveHint"></p>
  </section>

  <section class="card tight">
    <table class="table compact">
      <thead><tr><th>Team</th><th>Score</th><th>Overs</th></tr></thead>
      <tbody>
        <tr><td id="tAname">Team A</td><td id="tAscore">0/0</td><td id="tAovers">0.0</td></tr>
        <tr><td id="tBname">Team B</td><td id="tBscore">0/0</td><td id="tBovers">0.0</td></tr>
      </tbody>
    </table>
    <div class="extras mt">
      <div class="extras-row">
        <span class="chip">NB: <b id="x_nb">0</b></span>
        <span class="chip">WD: <b id="x_wd">0</b></span>
        <span class="chip">B: <b id="x_b">0</b></span>
        <span class="chip">LB: <b id="x_lb">0</b></span>
      </div>
    </div>
  </section>

  <!-- Stats/log collapse omitted for brevity if you already have them -->

  <footer class="hint center">Tip: Use the dropdown for quick entries; wides/noballs don’t consume the ball.</footer>
</main>

<!-- Scoring Dock (pad buttons) -->
<nav class="dock">
  <div class="padgrid" id="padgrid">
    <button class="btn big" data-ev="dot">·</button>
    <button class="btn big" data-ev="1">1</button>
    <button class="btn big" data-ev="2">2</button>
    <button class="btn big" data-ev="3">3</button>
    <button class="btn big" data-ev="4">4</button>
    <button class="btn big" data-ev="6">6</button>
    <button class="btn big accent" data-ev="noball">NB</button>
    <button class="btn big" data-ev="wide">WD</button>
    <button class="btn big" data-ev="bye">B</button>
    <button class="btn big" data-ev="legbye">LB</button>
    <button class="btn big danger" data-ev="wicket">Wkt</button>
    <button class="btn big danger" data-ev="undo">Undo</button>
  </div>
</nav>

<!-- NB modal -->
<div class="modal hidden" id="nbModal">
  <div class="modal-card">
    <div class="modal-title">No-ball — runs off the bat?</div>
    <div class="grid chips">
      <button class="chip nbf nbpick" data-val="0">0</button>
      <button class="chip nbf nbpick" data-val="1">1</button>
      <button class="chip nbf nbpick" data-val="2">2</button>
      <button class="chip nbf nbpick" data-val="3">3</button>
      <button class="chip nbf nbpick" data-val="4">4</button>
      <button class="chip nbf nbpick" data-val="6">6</button>
    </div>
    <div class="row mt center"><button class="btn ghost" id="nbCancel">Cancel</button></div>
  </div>
</div>

<!-- Wide modal -->
<div class="modal hidden" id="wdModal">
  <div class="modal-card">
    <div class="modal-title">Wide — how many?</div>
    <div class="grid chips">
      <button class="chip wdf wdpick" data-val="1">+1</button>
      <button class="chip wdf wdpick" data-val="2">+2</button>
      <button class="chip wdf wdpick" data-val="3">+3</button>
    </div>
    <div class="row mt center"><button class="btn ghost" id="wdCancel">Cancel</button></div>
  </div>
</div>

<!-- Pretty Player Picker modal -->
<div class="modal hidden" id="pickModal">
  <div class="modal-card">
    <div class="modal-title" id="pickTitle">Select Player</div>
    <input id="pickSearch" class="chip-input" placeholder="Type a name…">
    <div id="pickList" class="picker-list mt"></div>
    <div class="row mt center"><button class="btn ghost" id="pickCancel">Close</button></div>
  </div>
</div>

<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', ()=> navigator.serviceWorker.register('service-worker.js'));
  }
  window.State = window.State || {};
</script>
<script type="module" src="assets/js/app.js"></script>
</body>
</html>
