<?php
require_once __DIR__ . '/api/lib/InstallCheck.php';
use StumpVision\InstallCheck;

// Redirect to setup if already installed
InstallCheck::requireNotInstalled();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#0ea5e9">
  <title>StumpVision - Setup Wizard</title>
  <style>
    :root {
      --bg: #f8fafc;
      --card: #ffffff;
      --ink: #0f172a;
      --muted: #64748b;
      --line: #cbd5e1;
      --accent: #0ea5e9;
      --accent-dark: #0284c7;
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
        --accent-dark: #0369a1;
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
      line-height: 1.6;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .container {
      width: 100%;
      max-width: 500px;
    }

    .logo {
      text-align: center;
      margin-bottom: 32px;
    }

    .logo h1 {
      font-size: 32px;
      font-weight: 900;
      color: var(--accent);
      margin-bottom: 8px;
    }

    .logo p {
      color: var(--muted);
      font-size: 14px;
    }

    .card {
      background: var(--card);
      border: 2px solid var(--line);
      border-radius: 16px;
      padding: 32px;
      box-shadow: 0 4px 20px var(--shadow);
    }

    h2 {
      font-size: 24px;
      margin-bottom: 8px;
      color: var(--ink);
    }

    .subtitle {
      color: var(--muted);
      margin-bottom: 24px;
      font-size: 14px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    label {
      display: block;
      font-weight: 600;
      margin-bottom: 8px;
      color: var(--ink);
      font-size: 14px;
    }

    input {
      width: 100%;
      padding: 12px 16px;
      border: 2px solid var(--line);
      border-radius: 8px;
      background: var(--bg);
      color: var(--ink);
      font-size: 16px;
      font-family: inherit;
      transition: border-color 0.2s;
    }

    input:focus {
      outline: none;
      border-color: var(--accent);
    }

    .password-hint {
      font-size: 12px;
      color: var(--muted);
      margin-top: 4px;
    }

    .error {
      background: #fee2e2;
      color: #991b1b;
      border: 2px solid #fca5a5;
      border-radius: 8px;
      padding: 12px 16px;
      margin-bottom: 20px;
      font-size: 14px;
    }

    .success {
      background: #dcfce7;
      color: #166534;
      border: 2px solid #86efac;
      border-radius: 8px;
      padding: 12px 16px;
      margin-bottom: 20px;
      font-size: 14px;
    }

    button {
      width: 100%;
      padding: 14px 24px;
      background: var(--accent);
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.2s;
    }

    button:hover {
      background: var(--accent-dark);
    }

    button:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .spinner {
      display: inline-block;
      width: 16px;
      height: 16px;
      border: 2px solid white;
      border-top-color: transparent;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      margin-right: 8px;
      vertical-align: middle;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    .progress {
      display: none;
      margin-top: 20px;
      padding: 16px;
      background: var(--bg);
      border-radius: 8px;
      border: 2px solid var(--line);
    }

    .progress.active {
      display: block;
    }

    .progress-step {
      display: flex;
      align-items: center;
      padding: 8px 0;
      color: var(--muted);
      font-size: 14px;
    }

    .progress-step.active {
      color: var(--accent);
      font-weight: 600;
    }

    .progress-step.completed {
      color: var(--success);
    }

    .progress-step .icon {
      margin-right: 8px;
      width: 20px;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="logo">
      <h1>🏏 StumpVision</h1>
      <p>Cricket Scoring & Analytics</p>
    </div>

    <div class="card">
      <h2>Setup Wizard</h2>
      <p class="subtitle">Let's get your StumpVision installation up and running!</p>

      <div id="error" class="error" style="display: none;"></div>
      <div id="success" class="success" style="display: none;"></div>

      <form id="setupForm">
        <div class="form-group">
          <label for="siteName">Site Name</label>
          <input
            type="text"
            id="siteName"
            name="siteName"
            placeholder="e.g., My Cricket Club"
            required
            maxlength="100"
            value="StumpVision"
          />
        </div>

        <div class="form-group">
          <label for="adminUsername">Admin Username</label>
          <input
            type="text"
            id="adminUsername"
            name="adminUsername"
            placeholder="admin"
            required
            minlength="3"
            maxlength="50"
            pattern="[a-zA-Z0-9_]+"
            value="admin"
          />
          <p class="password-hint">Letters, numbers, and underscores only</p>
        </div>

        <div class="form-group">
          <label for="adminPassword">Admin Password</label>
          <input
            type="password"
            id="adminPassword"
            name="adminPassword"
            placeholder="••••••••"
            required
            minlength="8"
          />
          <p class="password-hint">At least 8 characters</p>
        </div>

        <div class="form-group">
          <label for="adminPasswordConfirm">Confirm Password</label>
          <input
            type="password"
            id="adminPasswordConfirm"
            name="adminPasswordConfirm"
            placeholder="••••••••"
            required
            minlength="8"
          />
        </div>

        <button type="submit" id="submitBtn">
          Complete Setup
        </button>
      </form>

      <div id="progress" class="progress">
        <div class="progress-step" id="step1">
          <span class="icon">⏳</span>
          <span>Creating database...</span>
        </div>
        <div class="progress-step" id="step2">
          <span class="icon">⏳</span>
          <span>Running migrations...</span>
        </div>
        <div class="progress-step" id="step3">
          <span class="icon">⏳</span>
          <span>Creating admin account...</span>
        </div>
        <div class="progress-step" id="step4">
          <span class="icon">⏳</span>
          <span>Finalizing setup...</span>
        </div>
      </div>
    </div>
  </div>

  <script>
    const form = document.getElementById('setupForm');
    const submitBtn = document.getElementById('submitBtn');
    const errorDiv = document.getElementById('error');
    const successDiv = document.getElementById('success');
    const progressDiv = document.getElementById('progress');

    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const password = document.getElementById('adminPassword').value;
      const passwordConfirm = document.getElementById('adminPasswordConfirm').value;

      if (password !== passwordConfirm) {
        showError('Passwords do not match!');
        return;
      }

      const data = {
        site_name: document.getElementById('siteName').value,
        admin_username: document.getElementById('adminUsername').value,
        admin_password: password
      };

      await runSetup(data);
    });

    async function runSetup(data) {
      hideMessages();
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner"></span> Setting up...';
      progressDiv.classList.add('active');

      try {
        // Step 1: Create database
        updateStep('step1', 'active');
        await sleep(500);

        const response = await fetch('api/install.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        });

        const result = await response.json();

        if (!result.ok) {
          throw new Error(result.err || 'Setup failed');
        }

        // Animate through steps
        updateStep('step1', 'completed', '✓');
        await sleep(300);

        updateStep('step2', 'active');
        await sleep(800);
        updateStep('step2', 'completed', '✓');
        await sleep(300);

        updateStep('step3', 'active');
        await sleep(600);
        updateStep('step3', 'completed', '✓');
        await sleep(300);

        updateStep('step4', 'active');
        await sleep(400);
        updateStep('step4', 'completed', '✓');

        await sleep(500);

        showSuccess('Setup completed successfully! Redirecting to admin login...');

        setTimeout(() => {
          window.location.href = 'admin/login.php';
        }, 2000);

      } catch (error) {
        console.error('Setup error:', error);
        showError(error.message || 'Setup failed. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Complete Setup';
        progressDiv.classList.remove('active');
        resetSteps();
      }
    }

    function updateStep(stepId, status, icon = '⏳') {
      const step = document.getElementById(stepId);
      step.className = 'progress-step ' + status;
      step.querySelector('.icon').textContent = icon;
    }

    function resetSteps() {
      ['step1', 'step2', 'step3', 'step4'].forEach(id => {
        updateStep(id, '', '⏳');
      });
    }

    function showError(message) {
      errorDiv.textContent = message;
      errorDiv.style.display = 'block';
      successDiv.style.display = 'none';
    }

    function showSuccess(message) {
      successDiv.textContent = message;
      successDiv.style.display = 'block';
      errorDiv.style.display = 'none';
    }

    function hideMessages() {
      errorDiv.style.display = 'none';
      successDiv.style.display = 'none';
    }

    function sleep(ms) {
      return new Promise(resolve => setTimeout(resolve, ms));
    }
  </script>
</body>
</html>
