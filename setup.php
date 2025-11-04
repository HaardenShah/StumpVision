<?php
require_once __DIR__ . '/api/lib/InstallCheck.php';
use StumpVision\InstallCheck;

// Redirect to installer if not installed
InstallCheck::requireInstalled();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <meta name="theme-color" content="#ffffff">
  <meta name="description" content="Set up your cricket match">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="mobile-web-app-capable" content="yes">
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
      --success: #16a34a;
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

    /* Wizard Step Management */
    .wizard-step {
      display: none;
    }

    .wizard-step.active {
      display: block;
      animation: fadeIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Progress Indicator */
    .progress-indicator {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
      padding: 0 20px;
      position: relative;
    }

    .progress-indicator::before {
      content: '';
      position: absolute;
      top: 15px;
      left: 40px;
      right: 40px;
      height: 3px;
      background: var(--line);
      z-index: 0;
    }

    .progress-step {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      position: relative;
      z-index: 1;
      flex: 1;
    }

    .progress-step-circle {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      background: var(--card);
      border: 3px solid var(--line);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      font-weight: 700;
      color: var(--muted);
      transition: all 0.3s;
    }

    .progress-step.completed .progress-step-circle {
      background: var(--success);
      border-color: var(--success);
      color: white;
    }

    .progress-step.active .progress-step-circle {
      background: var(--accent);
      border-color: var(--accent);
      color: white;
      transform: scale(1.2);
    }

    .progress-step-label {
      font-size: 10px;
      color: var(--muted);
      text-align: center;
      font-weight: 600;
      max-width: 80px;
    }

    .progress-step.active .progress-step-label {
      color: var(--accent);
      font-weight: 700;
    }

    /* Wizard Navigation */
    .wizard-nav {
      display: flex;
      gap: 12px;
      margin-top: 24px;
    }

    .wizard-nav button {
      flex: 1;
      padding: 14px;
      border-radius: 12px;
      font-weight: 700;
      font-size: 15px;
      cursor: pointer;
      transition: all 0.2s;
      border: none;
    }

    .wizard-nav .btn-prev {
      background: var(--card);
      color: var(--ink);
      border: 2px solid var(--line);
    }

    .wizard-nav .btn-prev:hover {
      border-color: var(--accent);
      color: var(--accent);
    }

    .wizard-nav .btn-next {
      background: var(--accent);
      color: white;
      box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
    }

    .wizard-nav .btn-next:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(14, 165, 233, 0.4);
    }

    .wizard-nav button:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none !important;
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
      padding: 12px;
      border: 2px dashed transparent;
      border-radius: 12px;
      transition: all 0.2s;
    }

    .player-tags.drag-over {
      border-color: var(--accent);
      background: var(--accent-light);
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
      cursor: move;
      user-select: none;
      transition: all 0.2s;
    }

    .player-tag:hover {
      transform: scale(1.05);
      box-shadow: 0 2px 8px var(--shadow);
    }

    .player-tag.dragging {
      opacity: 0.4;
      transform: scale(0.95);
    }

    .player-tag.verified {
      background: #dcfce7;
      border-color: #16a34a;
    }

    @media (prefers-color-scheme: dark) {
      .player-tag.verified {
        background: #14532d;
        border-color: #22c55e;
      }
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

    .verified-badge {
      background: #16a34a;
      color: white;
      padding: 2px 6px;
      border-radius: 4px;
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
    }

    .player-input-container {
      position: relative;
    }

    .code-input {
      margin-top: 8px;
      display: none;
    }

    .code-input.active {
      display: block;
    }

    .code-input input {
      font-family: monospace;
      text-transform: uppercase;
      font-weight: 600;
    }

    .add-player-btn {
      width: 100%;
      padding: 10px;
      background: var(--accent);
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      margin-top: 8px;
      transition: all 0.2s;
    }

    .add-player-btn:hover {
      opacity: 0.9;
    }

    .add-player-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .verification-status {
      font-size: 12px;
      margin-top: 4px;
      padding: 6px 10px;
      border-radius: 6px;
      display: none;
    }

    .verification-status.active {
      display: block;
    }

    .verification-status.success {
      background: #dcfce7;
      color: #166534;
      border: 1px solid #16a34a;
    }

    .verification-status.error {
      background: #fee2e2;
      color: #991b1b;
      border: 1px solid #dc2626;
    }

    @media (prefers-color-scheme: dark) {
      .verification-status.success {
        background: #14532d;
        color: #86efac;
      }
      .verification-status.error {
        background: #7f1d1d;
        color: #fca5a5;
      }
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

    .quick-add-section {
      background: #f0f9ff;
      border: 2px dashed var(--accent);
      border-radius: 12px;
      padding: 12px;
      margin-bottom: 16px;
    }

    @media (prefers-color-scheme: dark) {
      .quick-add-section {
        background: #082f49;
      }
    }

    .quick-add-title {
      font-size: 13px;
      font-weight: 700;
      color: var(--accent);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .quick-add-title::before {
      content: '';
      display: none;
    }

    .quick-add-input {
      font-family: monospace;
      text-transform: uppercase;
      font-weight: 600;
      font-size: 14px;
    }

    .quick-add-hint {
      font-size: 11px;
      color: var(--muted);
      margin-top: 6px;
    }

    .quick-add-btn {
      width: 100%;
      padding: 8px;
      background: var(--accent);
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: 700;
      cursor: pointer;
      margin-top: 8px;
      transition: all 0.2s;
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .quick-add-btn:hover {
      opacity: 0.9;
    }

    .quick-add-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .bulk-status {
      font-size: 12px;
      margin-top: 8px;
      padding: 8px;
      border-radius: 6px;
      display: none;
    }

    .bulk-status.active {
      display: block;
    }

    .bulk-status.info {
      background: #dbeafe;
      color: #1e40af;
      border: 1px solid #3b82f6;
    }

    .bulk-status.success {
      background: #dcfce7;
      color: #166534;
      border: 1px solid #16a34a;
    }

    .bulk-status.error {
      background: #fee2e2;
      color: #991b1b;
      border: 1px solid #dc2626;
    }

    @media (prefers-color-scheme: dark) {
      .bulk-status.info {
        background: #1e3a8a;
        color: #93c5fd;
      }
      .bulk-status.success {
        background: #14532d;
        color: #86efac;
      }
      .bulk-status.error {
        background: #7f1d1d;
        color: #fca5a5;
      }
    }

    .load-match-section {
      background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
      border: 3px solid var(--accent);
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 20px;
    }

    @media (prefers-color-scheme: dark) {
      .load-match-section {
        background: linear-gradient(135deg, #082f49 0%, #0c4a6e 100%);
      }
    }

    .load-match-title {
      font-size: 18px;
      font-weight: 800;
      color: var(--accent);
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .load-match-title::before {
      content: '';
      display: none;
    }

    .load-match-subtitle {
      font-size: 13px;
      color: var(--muted);
      margin-bottom: 16px;
    }

    .match-id-input {
      font-family: monospace;
      font-size: 32px;
      font-weight: 800;
      text-align: center;
      letter-spacing: 4px;
      padding: 16px;
      width: 100%;
      margin-bottom: 12px;
    }

    .load-match-btn {
      width: 100%;
      padding: 14px;
      background: var(--accent);
      color: white;
      border: none;
      border-radius: 12px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s;
      font-size: 16px;
    }

    .load-match-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
    }

    .load-match-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .divider {
      text-align: center;
      margin: 24px 0;
      position: relative;
    }

    .divider::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 0;
      right: 0;
      height: 2px;
      background: var(--line);
    }

    .divider span {
      background: var(--bg);
      padding: 0 16px;
      position: relative;
      color: var(--muted);
      font-size: 14px;
      font-weight: 600;
    }

    .team-assignment-section {
      display: none;
    }

    .team-assignment-section.active {
      display: block;
    }

    .unassigned-players {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      padding: 12px;
      background: var(--bg);
      border: 2px dashed var(--line);
      border-radius: 12px;
      min-height: 60px;
      margin-bottom: 16px;
    }

    .player-chip {
      background: var(--card);
      border: 2px solid var(--line);
      color: var(--ink);
      padding: 8px 16px;
      border-radius: 999px;
      font-size: 14px;
      font-weight: 600;
      cursor: move;
      transition: all 0.2s;
      user-select: none;
    }

    .player-chip:hover {
      border-color: var(--accent);
      transform: scale(1.05);
    }

    .player-chip.dragging {
      opacity: 0.5;
    }

    .auto-assign-btn {
      width: 100%;
      padding: 12px;
      background: var(--accent-light);
      color: var(--accent);
      border: 2px solid var(--accent);
      border-radius: 12px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s;
      font-size: 15px;
      margin-bottom: 16px;
    }

    .auto-assign-btn:hover {
      background: var(--accent);
      color: white;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>StumpVision</h1>
      <p>Match Setup Wizard</p>
      <p style="font-size: 12px; color: var(--muted); margin-top: 4px;">Follow the steps to set up your match</p>
    </div>

    <!-- Progress Indicator -->
    <div class="progress-indicator" id="progressIndicator">
      <div class="progress-step" data-step="1">
        <div class="progress-step-circle">1</div>
        <div class="progress-step-label">Load</div>
      </div>
      <div class="progress-step" data-step="2">
        <div class="progress-step-circle">2</div>
        <div class="progress-step-label">Details</div>
      </div>
      <div class="progress-step" data-step="3">
        <div class="progress-step-circle">3</div>
        <div class="progress-step-label">Teams</div>
      </div>
      <div class="progress-step" data-step="4">
        <div class="progress-step-circle">4</div>
        <div class="progress-step-label">Toss</div>
      </div>
      <div class="progress-step" data-step="5">
        <div class="progress-step-circle">5</div>
        <div class="progress-step-label">Openers</div>
      </div>
      <div class="progress-step" data-step="6">
        <div class="progress-step-circle">6</div>
        <div class="progress-step-label">Start</div>
      </div>
    </div>

    <div id="errorMsg" class="error" style="display: none;"></div>

    <!-- STEP 1: Load or Create Match -->
    <div class="wizard-step" id="step1" data-step="1">
      <div class="load-match-section">
        <div class="load-match-title">Load Scheduled Match</div>
        <p class="load-match-subtitle">Enter the 6-digit Match ID to load a pre-scheduled match</p>
        <input type="text" id="matchIdInput" class="match-id-input" placeholder="000000" maxlength="6" pattern="[0-9]{6}">
        <button type="button" class="load-match-btn" id="loadMatchBtn">Load Match</button>
        <div class="verification-status" id="loadMatchStatus"></div>
      </div>

      <div class="divider"><span>OR CREATE NEW MATCH</span></div>

      <div class="wizard-nav">
        <button type="button" class="btn-next" onclick="nextStep()">Continue →</button>
      </div>
    </div>

    <!-- STEP 2: Match Details -->
    <div class="wizard-step" id="step2" data-step="2">
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

      <div class="wizard-nav">
        <button type="button" class="btn-prev" onclick="prevStep()">← Back</button>
        <button type="button" class="btn-next" onclick="nextStep()">Continue →</button>
      </div>
    </div>

    <!-- STEP 3: Team Configuration -->
    <div class="wizard-step" id="step3" data-step="3">
      <!-- Team Assignment Section (shown after loading match with unassigned players) -->
      <div class="team-assignment-section" id="teamAssignmentSection">
        <div class="card">
          <div class="card-title">Assign Players to Teams</div>
          <button type="button" class="auto-assign-btn" id="autoAssignBtn">Auto-Assign Teams Randomly</button>

          <div class="form-group">
            <label>Unassigned Players (Drag to teams below)</label>
            <div class="unassigned-players" id="unassignedPlayers"></div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-title">Team A</div>
        <div class="form-group">
          <label>Team Name</label>
          <input type="text" id="teamAName" placeholder="Enter team name" value="Team A">
        </div>
        <div class="form-group">
          <label>Players (drag to reorder or move between teams)</label>

          <!-- Quick Add Section -->
          <div class="quick-add-section">
            <div class="quick-add-title">Quick Add by Code</div>
            <input type="text" id="teamAQuickAdd" placeholder="JOSM-1234 JADO-5678 MIWI-9012" class="quick-add-input">
            <div class="bulk-status" id="teamABulkStatus"></div>
            <button type="button" class="quick-add-btn" id="teamAQuickAddBtn">Add Players</button>
            <p class="quick-add-hint">Enter one or more player codes separated by spaces</p>
          </div>

          <div class="player-input-container">
            <input type="text" id="teamAPlayerInput" placeholder="Player name" class="players-input">
            <div class="code-input" id="teamACodeInput">
              <input type="text" id="teamAPlayerCode" placeholder="Player code (optional, e.g. JOSM-1234)" maxlength="9">
            </div>
            <div class="verification-status" id="teamAVerificationStatus"></div>
            <button type="button" class="add-player-btn" id="teamAAddBtn">Add Player</button>
          </div>
          <div class="player-tags" id="teamAPlayers"></div>
          <p class="hint">Enter name and optionally add player code for verification</p>
        </div>
      </div>

      <div class="card">
        <div class="card-title">Team B</div>
        <div class="form-group">
          <label>Team Name</label>
          <input type="text" id="teamBName" placeholder="Enter team name" value="Team B">
        </div>
        <div class="form-group">
          <label>Players (drag to reorder or move between teams)</label>

          <!-- Quick Add Section -->
          <div class="quick-add-section">
            <div class="quick-add-title">Quick Add by Code</div>
            <input type="text" id="teamBQuickAdd" placeholder="JOSM-1234 JADO-5678 MIWI-9012" class="quick-add-input">
            <div class="bulk-status" id="teamBBulkStatus"></div>
            <button type="button" class="quick-add-btn" id="teamBQuickAddBtn">Add Players</button>
            <p class="quick-add-hint">Enter one or more player codes separated by spaces</p>
          </div>

          <div class="player-input-container">
            <input type="text" id="teamBPlayerInput" placeholder="Player name" class="players-input">
            <div class="code-input" id="teamBCodeInput">
              <input type="text" id="teamBPlayerCode" placeholder="Player code (optional, e.g. JOSM-1234)" maxlength="9">
            </div>
            <div class="verification-status" id="teamBVerificationStatus"></div>
            <button type="button" class="add-player-btn" id="teamBAddBtn">Add Player</button>
          </div>
          <div class="player-tags" id="teamBPlayers"></div>
          <p class="hint">Enter name and optionally add player code for verification</p>
        </div>
      </div>

      <div class="wizard-nav">
        <button type="button" class="btn-prev" onclick="prevStep()">← Back</button>
        <button type="button" class="btn-next" onclick="nextStep()">Continue →</button>
      </div>
    </div>

    <!-- STEP 4: Toss -->
    <div class="wizard-step" id="step4" data-step="4">
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

      <div class="wizard-nav">
        <button type="button" class="btn-prev" onclick="prevStep()">← Back</button>
        <button type="button" class="btn-next" onclick="nextStep()">Continue →</button>
      </div>
    </div>

    <!-- STEP 5: Opening Players -->
    <div class="wizard-step" id="step5" data-step="5">
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
        <p class="hint">Select opening batsmen from the batting team and opening bowler from the bowling team</p>
      </div>

      <div class="wizard-nav">
        <button type="button" class="btn-prev" onclick="prevStep()">← Back</button>
        <button type="button" class="btn-next" onclick="nextStep()">Review →</button>
      </div>
    </div>

    <!-- STEP 6: Review and Start -->
    <div class="wizard-step" id="step6" data-step="6">
      <div class="card">
        <div class="card-title">Review Match Setup</div>
        <div id="reviewContent"></div>
      </div>

      <div class="wizard-nav">
        <button type="button" class="btn-prev" onclick="prevStep()">← Back</button>
        <button type="button" class="btn-next" id="startMatch">Start Match</button>
      </div>
    </div>
  </div>

  <script>
    // Wizard State
    let currentStep = 1;
    const totalSteps = 6;

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
      openingBowler: null,
      loadedMatchId: null,
      unassignedPlayers: []
    };

    // Wizard Navigation Functions
    function goToStep(step) {
      // Hide error message when changing steps
      document.getElementById('errorMsg').style.display = 'none';

      // Hide all steps
      document.querySelectorAll('.wizard-step').forEach(el => {
        el.classList.remove('active');
      });

      // Show target step
      document.getElementById(`step${step}`).classList.add('active');
      currentStep = step;

      // Update progress indicator
      updateProgressIndicator();

      // Update team assignment section visibility
      if (step === 3) {
        document.getElementById('teamAssignmentSection').classList.toggle('active',
          state.unassignedPlayers.length > 0);
      }

      // Generate review content if on step 6
      if (step === 6) {
        generateReviewContent();
      }

      // Scroll to top
      window.scrollTo(0, 0);
    }

    function nextStep() {
      // Validation before moving forward
      if (currentStep === 3 && !validateTeams()) {
        return;
      }
      if (currentStep === 5 && !validateOpeningPlayers()) {
        return;
      }

      if (currentStep < totalSteps) {
        goToStep(currentStep + 1);
      }
    }

    function prevStep() {
      if (currentStep > 1) {
        goToStep(currentStep - 1);
      }
    }

    function updateProgressIndicator() {
      document.querySelectorAll('.progress-step').forEach(el => {
        const step = parseInt(el.dataset.step);
        el.classList.remove('active', 'completed');

        if (step === currentStep) {
          el.classList.add('active');
        } else if (step < currentStep) {
          el.classList.add('completed');
          el.querySelector('.progress-step-circle').innerHTML = '&#10003;';
        } else {
          el.querySelector('.progress-step-circle').textContent = step;
        }
      });
    }

    function validateTeams() {
      const errorMsg = document.getElementById('errorMsg');

      if (!state.teamA.name.trim() || !state.teamB.name.trim()) {
        errorMsg.textContent = 'Please enter names for both teams';
        errorMsg.style.display = 'block';
        return false;
      }

      if (state.teamA.players.length < 2) {
        errorMsg.textContent = 'Team A needs at least 2 players';
        errorMsg.style.display = 'block';
        return false;
      }

      if (state.teamB.players.length < 2) {
        errorMsg.textContent = 'Team B needs at least 2 players';
        errorMsg.style.display = 'block';
        return false;
      }

      return true;
    }

    function validateOpeningPlayers() {
      const errorMsg = document.getElementById('errorMsg');

      if (!state.openingBat1 || !state.openingBat2) {
        errorMsg.textContent = 'Please select both opening batsmen';
        errorMsg.style.display = 'block';
        return false;
      }

      if (state.openingBat1 === state.openingBat2) {
        errorMsg.textContent = 'Opening batsmen must be different players';
        errorMsg.style.display = 'block';
        return false;
      }

      if (!state.openingBowler) {
        errorMsg.textContent = 'Please select an opening bowler';
        errorMsg.style.display = 'block';
        return false;
      }

      return true;
    }

    function generateReviewContent() {
      const container = document.getElementById('reviewContent');
      const battingTeam = state.tossDecision === 'bat' ? state.tossWinner :
                          (state.tossWinner === 'teamA' ? 'teamB' : 'teamA');
      const battingTeamName = state[battingTeam].name;
      const bowlingTeamName = state[battingTeam === 'teamA' ? 'teamB' : 'teamA'].name;

      container.innerHTML = `
        <div style="padding: 16px; background: var(--bg); border-radius: 8px; margin-bottom: 16px;">
          <h3 style="font-size: 14px; color: var(--muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em;">Match Format</h3>
          <p style="font-size: 16px; font-weight: 600;">${state.matchFormat === 'limited' ? 'Limited Overs' : 'Test Match'} - ${state.oversPerInnings} overs, ${state.wicketsLimit} wickets</p>
        </div>

        <div style="padding: 16px; background: var(--bg); border-radius: 8px; margin-bottom: 16px;">
          <h3 style="font-size: 14px; color: var(--muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em;">Teams</h3>
          <p style="font-size: 16px; font-weight: 600; margin-bottom: 4px;">${state.teamA.name} (${state.teamA.players.length} players)</p>
          <p style="font-size: 14px; color: var(--muted); margin-bottom: 12px;">${state.teamA.players.map(p => p.name).join(', ')}</p>

          <p style="font-size: 16px; font-weight: 600; margin-bottom: 4px;">${state.teamB.name} (${state.teamB.players.length} players)</p>
          <p style="font-size: 14px; color: var(--muted);">${state.teamB.players.map(p => p.name).join(', ')}</p>
        </div>

        <div style="padding: 16px; background: var(--bg); border-radius: 8px; margin-bottom: 16px;">
          <h3 style="font-size: 14px; color: var(--muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em;">Toss</h3>
          <p style="font-size: 16px; font-weight: 600;">${state[state.tossWinner].name} won the toss and elected to ${state.tossDecision === 'bat' ? 'bat first' : 'bowl first'}</p>
        </div>

        <div style="padding: 16px; background: var(--bg); border-radius: 8px;">
          <h3 style="font-size: 14px; color: var(--muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em;">Opening Players</h3>
          <p style="font-size: 16px; font-weight: 600; margin-bottom: 4px;">Batting: ${battingTeamName}</p>
          <p style="font-size: 14px; color: var(--muted); margin-bottom: 8px;">${state.openingBat1} and ${state.openingBat2}</p>
          <p style="font-size: 16px; font-weight: 600; margin-bottom: 4px;">Bowling: ${bowlingTeamName}</p>
          <p style="font-size: 14px; color: var(--muted);">${state.openingBowler}</p>
        </div>
      `;
    }

    // Load scheduled match by ID
    async function loadScheduledMatch(matchId) {
      try {
        const response = await fetch(`/api/scheduled-matches.php?action=get&id=${matchId}`);
        const result = await response.json();

        if (result.ok && result.match) {
          const match = result.match;

          // Load match details
          state.matchFormat = match.matchFormat;
          state.oversPerInnings = match.oversPerInnings;
          state.wicketsLimit = match.wicketsLimit;
          state.loadedMatchId = matchId;

          // Set unassigned players
          state.unassignedPlayers = match.players.map(p => ({
            name: p.name,
            verified: true,
            playerId: p.id,
            code: p.code
          }));

          // If teams are already assigned, load them
          if (match.teamA && match.teamA.players && match.teamA.players.length > 0) {
            state.teamA = match.teamA;
            state.teamB = match.teamB;
          } else {
            // Teams not assigned yet, show assignment UI
            state.teamA = { name: match.teamA?.name || 'Team A', players: [] };
            state.teamB = { name: match.teamB?.name || 'Team B', players: [] };
          }

          // Update form fields
          document.getElementById('matchFormat').value = state.matchFormat;
          document.getElementById('oversPerInnings').value = state.oversPerInnings;
          document.getElementById('wicketsLimit').value = state.wicketsLimit;
          document.getElementById('teamAName').value = state.teamA.name;
          document.getElementById('teamBName').value = state.teamB.name;

          // Show team assignment UI if teams not yet assigned
          if (state.unassignedPlayers.length > 0) {
            renderUnassignedPlayers();
            document.getElementById('teamAssignmentSection').classList.add('active');
          }

          renderPlayers('teamA');
          renderPlayers('teamB');
          updateOpeningSelects();

          return { success: true, message: `Match ${matchId} loaded successfully!` };
        } else {
          return { success: false, message: 'Match not found' };
        }
      } catch (err) {
        console.error('Error loading match:', err);
        return { success: false, message: 'Failed to load match' };
      }
    }

    // Render unassigned players
    function renderUnassignedPlayers() {
      const container = document.getElementById('unassignedPlayers');
      container.innerHTML = '';

      state.unassignedPlayers.forEach((player, index) => {
        const chip = document.createElement('div');
        chip.className = 'player-chip';
        chip.textContent = player.name;
        chip.draggable = true;
        chip.dataset.playerIndex = index;

        // Drag events
        chip.addEventListener('dragstart', (e) => {
          e.dataTransfer.setData('text/plain', index);
          chip.classList.add('dragging');
        });

        chip.addEventListener('dragend', () => {
          chip.classList.remove('dragging');
        });

        container.appendChild(chip);
      });
    }

    // Auto-assign teams randomly
    function autoAssignTeams() {
      if (state.unassignedPlayers.length === 0) {
        return;
      }

      // Shuffle players
      const shuffled = [...state.unassignedPlayers].sort(() => Math.random() - 0.5);

      // Split into two teams
      const half = Math.ceil(shuffled.length / 2);
      state.teamA.players = shuffled.slice(0, half);
      state.teamB.players = shuffled.slice(half);
      state.unassignedPlayers = [];

      renderUnassignedPlayers();
      renderPlayers('teamA');
      renderPlayers('teamB');
      updateOpeningSelects();

      // Hide assignment section
      document.getElementById('teamAssignmentSection').classList.remove('active');
    }

    // Move player from unassigned to team
    function movePlayerToTeam(playerIndex, team) {
      if (playerIndex < 0 || playerIndex >= state.unassignedPlayers.length) {
        return;
      }

      const player = state.unassignedPlayers[playerIndex];
      state[team].players.push(player);
      state.unassignedPlayers.splice(playerIndex, 1);

      renderUnassignedPlayers();
      renderPlayers(team);
      updateOpeningSelects();

      // Hide assignment section if all players assigned
      if (state.unassignedPlayers.length === 0) {
        document.getElementById('teamAssignmentSection').classList.remove('active');
      }
    }

    // Setup drag and drop for team containers
    function setupDragDrop() {
      const teamAContainer = document.getElementById('teamAPlayers');
      const teamBContainer = document.getElementById('teamBPlayers');
      const unassignedContainer = document.getElementById('unassignedPlayers');

      // Setup drop zones for teams
      [teamAContainer, teamBContainer].forEach(container => {
        container.addEventListener('dragover', (e) => {
          e.preventDefault();
          e.dataTransfer.dropEffect = 'move';
          container.classList.add('drag-over');
        });

        container.addEventListener('dragleave', (e) => {
          if (e.target === container) {
            container.classList.remove('drag-over');
          }
        });

        container.addEventListener('drop', (e) => {
          e.preventDefault();
          container.classList.remove('drag-over');

          const targetTeam = container.id.replace('Players', '');
          const data = e.dataTransfer.getData('text/plain');

          try {
            // Try to parse as JSON (from team player)
            const dragData = JSON.parse(data);
            movePlayerBetweenTeams(dragData.team, dragData.index, targetTeam);
          } catch {
            // Parse as index (from unassigned players)
            const playerIndex = parseInt(data);
            if (!isNaN(playerIndex)) {
              movePlayerToTeam(playerIndex, targetTeam);
            }
          }
        });
      });

      // Setup drop zone for unassigned (to move players back)
      unassignedContainer.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        unassignedContainer.classList.add('drag-over');
      });

      unassignedContainer.addEventListener('dragleave', (e) => {
        if (e.target === unassignedContainer) {
          unassignedContainer.classList.remove('drag-over');
        }
      });

      unassignedContainer.addEventListener('drop', (e) => {
        e.preventDefault();
        unassignedContainer.classList.remove('drag-over');

        const data = e.dataTransfer.getData('text/plain');

        try {
          // Parse as JSON (from team player)
          const dragData = JSON.parse(data);
          movePlayerToUnassigned(dragData.team, dragData.index);
        } catch {
          // Ignore drops from unassigned to unassigned
        }
      });
    }

    // Move player between teams or within same team
    function movePlayerBetweenTeams(fromTeam, fromIndex, toTeam) {
      // Check if moving within same team (reordering) - ignore for now
      if (fromTeam === toTeam) {
        return;
      }

      // Check for duplicates
      const player = state[fromTeam].players[fromIndex];
      const exists = state[toTeam].players.some(p => p.name === player.name);
      if (exists) {
        const errorMsg = document.getElementById('errorMsg');
        errorMsg.textContent = `${player.name} is already in ${state[toTeam].name}`;
        errorMsg.style.display = 'block';
        setTimeout(() => {
          errorMsg.style.display = 'none';
        }, 3000);
        return;
      }

      // Move player
      state[toTeam].players.push(player);
      state[fromTeam].players.splice(fromIndex, 1);

      renderPlayers(fromTeam);
      renderPlayers(toTeam);
      updateOpeningSelects();
    }

    // Move player from team to unassigned
    function movePlayerToUnassigned(fromTeam, fromIndex) {
      const player = state[fromTeam].players[fromIndex];
      state.unassignedPlayers.push(player);
      state[fromTeam].players.splice(fromIndex, 1);

      renderPlayers(fromTeam);
      renderUnassignedPlayers();
      updateOpeningSelects();

      // Show assignment section if hidden
      if (state.unassignedPlayers.length > 0 && currentStep === 3) {
        document.getElementById('teamAssignmentSection').classList.add('active');
      }
    }

    // Event listener for Load Match button
    document.getElementById('loadMatchBtn').addEventListener('click', async () => {
      const matchId = document.getElementById('matchIdInput').value.trim();
      const statusEl = document.getElementById('loadMatchStatus');

      if (!matchId || matchId.length !== 6) {
        statusEl.textContent = 'Please enter a valid 6-digit Match ID';
        statusEl.className = 'verification-status active error';
        return;
      }

      statusEl.textContent = 'Loading match...';
      statusEl.className = 'verification-status active success';

      const result = await loadScheduledMatch(matchId);

      statusEl.textContent = result.message;
      statusEl.className = `verification-status active ${result.success ? 'success' : 'error'}`;

      if (result.success) {
        setTimeout(() => {
          statusEl.classList.remove('active');
          // Auto-advance to next step after successful load
          if (state.unassignedPlayers.length > 0) {
            // If there are unassigned players, go to step 3 (Team Configuration)
            goToStep(3);
          } else {
            // Otherwise go to step 2 (Match Details)
            goToStep(2);
          }
        }, 1500);
      }
    });

    // Event listener for match ID input Enter key
    document.getElementById('matchIdInput').addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('loadMatchBtn').click();
      }
    });

    // Auto-capitalize and format match ID input
    document.getElementById('matchIdInput').addEventListener('input', (e) => {
      e.target.value = e.target.value.replace(/[^0-9]/g, '');
    });

    // Event listener for Auto-Assign button
    document.getElementById('autoAssignBtn').addEventListener('click', autoAssignTeams);

    // Setup drag and drop
    setupDragDrop();

    // Player management with verification support
    async function verifyPlayerCode(name, code) {
      if (!code || !code.trim()) {
        return { verified: false };
      }

      try {
        const payload = { name: name.trim(), code: code.trim().toUpperCase() };
        console.log('Verification request:', payload);

        const response = await fetch('/api/players.php?action=verify', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });

        const result = await response.json();
        console.log('Verification response:', result);
        return result;
      } catch (err) {
        console.error('Verification error:', err);
        return { verified: false, error: true };
      }
    }

    // Quick add by code only
    async function lookupPlayerByCode(code) {
      if (!code || !code.trim()) {
        return { verified: false };
      }

      try {
        const payload = { code: code.trim().toUpperCase() };
        console.log('Code lookup request:', payload);

        const response = await fetch('/api/players.php?action=verify', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });

        const result = await response.json();
        console.log('Code lookup response:', result);
        return result;
      } catch (err) {
        console.error('Code lookup error:', err);
        return { verified: false, error: true };
      }
    }

    async function quickAddPlayers(team, codesInput) {
      if (!codesInput || !codesInput.trim()) {
        showBulkStatus(team, 'Please enter at least one player code', 'error');
        return;
      }

      // Parse codes (support space, comma, or newline separated)
      const codes = codesInput
        .trim()
        .toUpperCase()
        .split(/[\s,\n]+/)
        .filter(c => c.length > 0);

      if (codes.length === 0) {
        showBulkStatus(team, 'No valid codes found', 'error');
        return;
      }

      showBulkStatus(team, `Looking up ${codes.length} player code(s)...`, 'info');

      let added = 0;
      let failed = 0;
      const failedCodes = [];

      for (const code of codes) {
        // Check if already in team
        const exists = state[team].players.some(p => p.code === code);
        if (exists) {
          console.log(`Player with code ${code} already in team`);
          failed++;
          failedCodes.push(`${code} (already added)`);
          continue;
        }

        // Lookup player
        const result = await lookupPlayerByCode(code);

        if (result.ok && result.verified && result.player) {
          // Add to team
          state[team].players.push({
            name: result.player.name,
            verified: true,
            playerId: result.player.id,
            code: result.player.code
          });
          added++;
        } else {
          failed++;
          failedCodes.push(`${code} (not found)`);
        }
      }

      renderPlayers(team);

      // Show results
      let message = `Added ${added} player(s)`;
      if (failed > 0) {
        message += ` - ${failed} failed: ${failedCodes.join(', ')}`;
      }
      showBulkStatus(team, message, failed > 0 ? 'error' : 'success');

      // Clear input if all succeeded
      if (failed === 0) {
        document.getElementById(`${team}QuickAdd`).value = '';
        setTimeout(() => hideBulkStatus(team), 3000);
      }
    }

    function showBulkStatus(team, message, type) {
      const status = document.getElementById(`${team}BulkStatus`);
      status.textContent = message;
      status.className = `bulk-status active ${type}`;
    }

    function hideBulkStatus(team) {
      const status = document.getElementById(`${team}BulkStatus`);
      status.classList.remove('active');
    }

    async function addPlayer(team, name, code = '') {
      if (!name.trim()) return;

      // Check for duplicate player names
      const exists = state[team].players.some(p => p.name === name.trim());
      if (exists) {
        showVerificationStatus(team, 'Player already added to this team', 'error');
        return;
      }

      let playerData = {
        name: name.trim(),
        verified: false,
        playerId: null,
        code: null
      };

      // Verify if code provided
      if (code && code.trim()) {
        showVerificationStatus(team, 'Verifying player code...', 'success');
        const verification = await verifyPlayerCode(name, code);

        if (verification.ok && verification.verified && verification.player) {
          playerData.verified = true;
          playerData.playerId = verification.player.id;
          playerData.code = verification.player.code;
          playerData.name = verification.player.name; // Use registered name
          showVerificationStatus(team, `Verified as ${verification.player.name}`, 'success');
        } else {
          showVerificationStatus(team, 'Code invalid or doesn\'t match', 'error');
          setTimeout(() => hideVerificationStatus(team), 3000);
          return;
        }
      } else {
        showVerificationStatus(team, 'Added as guest player', 'success');
      }

      state[team].players.push(playerData);
      renderPlayers(team);

      // Clear inputs
      document.getElementById(`${team}PlayerInput`).value = '';
      document.getElementById(`${team}PlayerCode`).value = '';
      hideCodeInput(team);

      setTimeout(() => hideVerificationStatus(team), 2000);
    }

    function removePlayer(team, index) {
      state[team].players.splice(index, 1);
      renderPlayers(team);
    }

    function renderPlayers(team) {
      const container = document.getElementById(`${team}Players`);
      container.innerHTML = '';

      state[team].players.forEach((player, index) => {
        const tag = document.createElement('div');
        tag.className = player.verified ? 'player-tag verified' : 'player-tag';
        tag.draggable = true;
        tag.dataset.team = team;
        tag.dataset.playerIndex = index;

        // Drag events for player tags
        tag.addEventListener('dragstart', (e) => {
          e.dataTransfer.effectAllowed = 'move';
          e.dataTransfer.setData('text/plain', JSON.stringify({
            team: team,
            index: index,
            player: player
          }));
          tag.classList.add('dragging');
        });

        tag.addEventListener('dragend', () => {
          tag.classList.remove('dragging');
        });

        const nameSpan = document.createElement('span');
        nameSpan.textContent = player.name;

        if (player.verified) {
          const badge = document.createElement('span');
          badge.className = 'verified-badge';
          badge.textContent = 'Verified';
          tag.appendChild(badge);
        }

        const removeBtn = document.createElement('button');
        removeBtn.textContent = '×';
        removeBtn.setAttribute('type', 'button');
        removeBtn.setAttribute('aria-label', `Remove ${player.name}`);
        removeBtn.addEventListener('click', (e) => {
          e.stopPropagation(); // Prevent drag from triggering
          removePlayer(team, index);
        });

        tag.appendChild(nameSpan);
        tag.appendChild(removeBtn);
        container.appendChild(tag);
      });

      updateOpeningSelects();
    }

    function showCodeInput(team) {
      document.getElementById(`${team}CodeInput`).classList.add('active');
    }

    function hideCodeInput(team) {
      document.getElementById(`${team}CodeInput`).classList.remove('active');
    }

    function showVerificationStatus(team, message, type) {
      const status = document.getElementById(`${team}VerificationStatus`);
      status.textContent = message;
      status.className = `verification-status active ${type}`;
    }

    function hideVerificationStatus(team) {
      const status = document.getElementById(`${team}VerificationStatus`);
      status.classList.remove('active');
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
        battingPlayers.map(p => `<option value="${p.name}">${p.name}</option>`).join('');

      bat1Select.innerHTML = batOptions;
      bat2Select.innerHTML = batOptions;

      // Update bowler select
      const bowlerSelect = document.getElementById('openingBowler');
      const bowlingPlayers = state[bowlingTeam].players;

      bowlerSelect.innerHTML = '<option value="">Select player...</option>' +
        bowlingPlayers.map(p => `<option value="${p.name}">${p.name}</option>`).join('');

      // Restore previous selections if valid
      const battingNames = battingPlayers.map(p => p.name);
      const bowlingNames = bowlingPlayers.map(p => p.name);

      if (battingNames.includes(state.openingBat1)) {
        bat1Select.value = state.openingBat1;
      }
      if (battingNames.includes(state.openingBat2)) {
        bat2Select.value = state.openingBat2;
      }
      if (bowlingNames.includes(state.openingBowler)) {
        bowlerSelect.value = state.openingBowler;
      }
    }

    // Event listeners for Team A Quick Add
    const teamAQuickAdd = document.getElementById('teamAQuickAdd');
    const teamAQuickAddBtn = document.getElementById('teamAQuickAddBtn');

    teamAQuickAdd.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        const codes = teamAQuickAdd.value;
        quickAddPlayers('teamA', codes);
      }
    });

    teamAQuickAddBtn.addEventListener('click', () => {
      const codes = teamAQuickAdd.value;
      quickAddPlayers('teamA', codes);
    });

    // Event listeners for Team A
    const teamAInput = document.getElementById('teamAPlayerInput');
    const teamACodeInput = document.getElementById('teamAPlayerCode');
    const teamAAddBtn = document.getElementById('teamAAddBtn');

    teamAInput.addEventListener('input', (e) => {
      if (e.target.value.trim()) {
        showCodeInput('teamA');
      } else {
        hideCodeInput('teamA');
      }
    });

    teamAInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        const name = teamAInput.value;
        const code = teamACodeInput.value;
        addPlayer('teamA', name, code);
      }
    });

    teamACodeInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        const name = teamAInput.value;
        const code = teamACodeInput.value;
        addPlayer('teamA', name, code);
      }
    });

    teamAAddBtn.addEventListener('click', () => {
      const name = teamAInput.value;
      const code = teamACodeInput.value;
      addPlayer('teamA', name, code);
    });

    // Event listeners for Team B Quick Add
    const teamBQuickAdd = document.getElementById('teamBQuickAdd');
    const teamBQuickAddBtn = document.getElementById('teamBQuickAddBtn');

    teamBQuickAdd.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        const codes = teamBQuickAdd.value;
        quickAddPlayers('teamB', codes);
      }
    });

    teamBQuickAddBtn.addEventListener('click', () => {
      const codes = teamBQuickAdd.value;
      quickAddPlayers('teamB', codes);
    });

    // Event listeners for Team B
    const teamBInput = document.getElementById('teamBPlayerInput');
    const teamBCodeInput = document.getElementById('teamBPlayerCode');
    const teamBAddBtn = document.getElementById('teamBAddBtn');

    teamBInput.addEventListener('input', (e) => {
      if (e.target.value.trim()) {
        showCodeInput('teamB');
      } else {
        hideCodeInput('teamB');
      }
    });

    teamBInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        const name = teamBInput.value;
        const code = teamBCodeInput.value;
        addPlayer('teamB', name, code);
      }
    });

    teamBCodeInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        const name = teamBInput.value;
        const code = teamBCodeInput.value;
        addPlayer('teamB', name, code);
      }
    });

    teamBAddBtn.addEventListener('click', () => {
      const name = teamBInput.value;
      const code = teamBCodeInput.value;
      addPlayer('teamB', name, code);
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

    // Initialize wizard on page load
    document.addEventListener('DOMContentLoaded', () => {
      goToStep(1);
    });
  </script>
</body>
</html>