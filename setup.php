<?php
/* =============================================================================
 * StumpVision — setup.php
 * Mobile-first guided setup wizard.
 *
 * Steps:
 *   1) Basics        — title, overs, balls/over
 *   2) Teams/Players — team names + rosters
 *   3) Confirm       — toss result + opted to, choose openers & opening bowler
 *
 * Output:
 *   Writes a JSON payload into localStorage under key `stumpvision_setup_payload`
 *   which index.php will consume to prefill a new match.
 * ========================================================================== */
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <!-- NOTE: viewport-fit=cover ensures safe-area insets are respected on iOS -->
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover" />
  <meta name="theme-color" content="#0b1120" />
  <title>StumpVision — Setup</title>

  <!-- PWA assets (optional but nice to keep consistent) -->
  <link rel="manifest" href="manifest.webmanifest">
  <link rel="apple-touch-icon" href="assets/icons/icon-192.png">

  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body class="setup">

  <!-- Sticky app bar -->
  <header class="appbar">
    <div class="appbar-row">
      <div class="brand">🏏 StumpVision</div>
      <div class="muted">Setup</div>
    </div>
  </header>

  <main class="content no-dock">
    <!-- Stepper: click to jump between steps -->
    <nav class="stepper">
      <div class="step current" data-step="1">1. Basics</div>
      <div class="step" data-step="2">2. Teams</div>
      <div class="step" data-step="3">3. Confirm</div>
    </nav>

    <!-- ============================ -->
    <!-- Step 1: Basics               -->
    <!-- ============================ -->
    <section class="card step-panel" data-step="1">
      <div class="form grid2">
        <label class="field">
          <span>Match Title</span>
          <input id="s_title" placeholder="e.g., Sunday Pickup at Long's Park" />
        </label>

        <div class="field-row">
          <label class="field sm">
            <span>Overs/Side</span>
            <input id="s_overs" type="number" min="1" max="50" value="10" inputmode="numeric" />
          </label>
          <label class="field sm">
            <span>Balls/Over</span>
            <input id="s_bpo" type="number" min="4" max="10" value="6" inputmode="numeric" />
          </label>
        </div>
      </div>

      <div class="row mt gap">
        <button class="btn next">Next →</button>
      </div>
    </section>

    <!-- ============================ -->
    <!-- Step 2: Teams & Players      -->
    <!-- ============================ -->
    <section class="card step-panel hidden" data-step="2">
      <div class="form grid2">
        <label class="field">
          <span>Team A Name</span>
          <input id="s_teamA" value="Team A" />
        </label>
        <label class="field">
          <span>Team B Name</span>
          <input id="s_teamB" value="Team B" />
        </label>
      </div>

      <div class="grid2 mt">
        <div class="card lite">
          <div class="row space-between">
            <div class="k">Players — Team A</div>
            <button class="chip-btn small" data-add="A" type="button">Add</button>
          </div>
          <ul id="listA" class="plist"></ul>
        </div>

        <div class="card lite">
          <div class="row space-between">
            <div class="k">Players — Team B</div>
            <button class="chip-btn small" data-add="B" type="button">Add</button>
          </div>
          <ul id="listB" class="plist"></ul>
        </div>
      </div>

      <div class="row mt gap">
        <button class="btn ghost prev" type="button">← Back</button>
        <button class="btn next" type="button">Next →</button>
      </div>
    </section>

    <!-- ============================ -->
    <!-- Step 3: Confirm & Openers    -->
    <!-- (TOSS MOVED HERE)           -->
    <!-- ============================ -->
    <section class="card step-panel hidden" data-step="3">
      <h3 class="mt">Confirm match details</h3>

      <!-- Toss & decision (moved from Step 1 -> here) -->
      <div class="form grid2 mt">
        <label class="field sm">
          <span>Toss Won</span>
          <select id="s_toss">
            <option value="A">Team A</option>
            <option value="B">Team B</option>
          </select>
        </label>
        <label class="field sm">
          <span>Opted To</span>
          <select id="s_opted">
            <option value="bat">Bat</option>
            <option value="bowl">Bowl</option>
          </select>
        </label>
      </div>

      <!-- Opening selections -->
      <p class="hint mt" id="openersHint">Select striker, non-striker, and opening bowler.</p>

      <div class="form grid2 mt">
        <label class="field">
          <span>Striker (Opening Batter)</span>
          <select id="s_striker"></select>
        </label>
        <label class="field">
          <span>Non-striker (Opening Batter)</span>
          <select id="s_nonStriker"></select>
        </label>
      </div>

      <div class="form mt">
        <label class="field">
          <span>Opening Bowler</span>
          <select id="s_bowler"></select>
        </label>
      </div>

      <!-- Human-readable review -->
      <div id="review" class="review mt"></div>

      <div class="row mt gap">
        <button class="btn ghost prev" type="button">← Back</button>
        <button class="btn accent" id="startMatch" type="button">Start Match</button>
      </div>
    </section>

    <footer class="hint center mt">You can edit names later if needed.</footer>
  </main>

  <!-- Wizard logic (ES Module) -->
  <script type="module" src="assets/js/setup.js"></script>
</body>
</html>
