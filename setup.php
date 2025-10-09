<?php /* setup.php — StumpVision guided setup */ ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover" />
  <meta name="theme-color" content="#0b1120" />
  <title>StumpVision — Setup</title>
  <link rel="manifest" href="manifest.webmanifest">
  <link rel="apple-touch-icon" href="assets/icons/icon-192.png">
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body class="setup">

<header class="appbar">
  <div class="appbar-row">
    <div class="brand">🏏 StumpVision</div>
    <div class="muted">Setup</div>
  </div>
</header>

<main class="content no-dock">
  <!-- Stepper -->
  <nav class="stepper">
    <div class="step current" data-step="1">1. Basics</div>
    <div class="step" data-step="2">2. Teams</div>
    <div class="step" data-step="3">3. Confirm</div>
  </nav>

  <!-- Step 1: Basics -->
  <section class="card step-panel" data-step="1">
    <div class="form grid2">
      <label class="field">
        <span>Match Title</span>
        <input id="s_title" placeholder="e.g., Sunday Pickup at Long's Park">
      </label>

      <div class="field-row">
        <label class="field sm">
          <span>Overs/Side</span>
          <input id="s_overs" type="number" min="1" max="50" value="10" inputmode="numeric">
        </label>
        <label class="field sm">
          <span>Balls/Over</span>
          <input id="s_bpo" type="number" min="4" max="10" value="6" inputmode="numeric">
        </label>
      </div>

      <div class="field-row">
        <label class="field sm">
          <span>Toss Won</span>
          <select id="s_toss"><option value="A">Team A</option><option value="B">Team B</option></select>
        </label>
        <label class="field sm">
          <span>Opted To</span>
          <select id="s_opted"><option value="bat">Bat</option><option value="bowl">Bowl</option></select>
        </label>
      </div>
    </div>

    <div class="row mt gap">
      <button class="btn next">Next →</button>
    </div>
  </section>

  <!-- Step 2: Teams & Players -->
  <section class="card step-panel hidden" data-step="2">
    <div class="form grid2">
      <label class="field">
        <span>Team A Name</span>
        <input id="s_teamA" value="Team A">
      </label>
      <label class="field">
        <span>Team B Name</span>
        <input id="s_teamB" value="Team B">
      </label>
    </div>

    <div class="grid2 mt">
      <div class="card lite">
        <div class="row space-between">
          <div class="k">Players — Team A</div>
          <button class="chip-btn small" data-add="A">Add</button>
        </div>
        <ul id="listA" class="plist"></ul>
      </div>
      <div class="card lite">
        <div class="row space-between">
          <div class="k">Players — Team B</div>
          <button class="chip-btn small" data-add="B">Add</button>
        </div>
        <ul id="listB" class="plist"></ul>
      </div>
    </div>

    <div class="row mt gap">
      <button class="btn ghost prev">← Back</button>
      <button class="btn next">Next →</button>
    </div>
  </section>

  <!-- Step 3: Confirm -->
  <section class="card step-panel hidden" data-step="3">
    <h3>Review</h3>
    <div id="review" class="review"></div>
    <div class="row mt gap">
      <button class="btn ghost prev">← Back</button>
      <button class="btn accent" id="startMatch">Start Match</button>
    </div>
  </section>

  <footer class="hint center mt">You can edit player names in-match if needed.</footer>
</main>

<script type="module" src="assets/js/setup.js"></script>
</body>
</html>
