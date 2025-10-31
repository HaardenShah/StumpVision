<?php
declare(strict_types=1);
require_once 'auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (loginAdmin($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}

// If already logged in, redirect to dashboard
if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StumpVision Admin - Login</title>
    <style>
        :root {
            --bg: #0b1120;
            --card: #1e293b;
            --ink: #e2e8f0;
            --muted: #94a3b8;
            --line: #334155;
            --accent: #0ea5e9;
            --danger: #ef4444;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: var(--ink);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            background: var(--card);
            border-radius: 20px;
            padding: 40px;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        .logo {
            text-align: center;
            margin-bottom: 32px;
        }
        .logo h1 {
            font-size: 32px;
            font-weight: 900;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .logo p {
            color: var(--muted);
            font-size: 14px;
            margin-top: 8px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--muted);
        }
        input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--line);
            border-radius: 12px;
            background: var(--bg);
            color: var(--ink);
            font-size: 15px;
            transition: all 0.2s;
        }
        input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn:active {
            transform: translateY(0);
        }
        .error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .note {
            margin-top: 24px;
            padding: 16px;
            background: rgba(14, 165, 233, 0.1);
            border: 1px solid var(--accent);
            border-radius: 8px;
            font-size: 13px;
            color: var(--muted);
        }
        .note strong {
            color: var(--accent);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>StumpVision</h1>
            <p>Admin Dashboard</p>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn">Login</button>
        </form>

        <div class="note">
            <strong>Default credentials:</strong><br>
            Username: admin<br>
            Password: changeme<br><br>
            <strong>⚠️ You will be prompted to change the default password after logging in.</strong>
        </div>
    </div>
</body>
</html>
