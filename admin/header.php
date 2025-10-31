<header class="admin-header">
    <div class="header-content">
        <div class="logo">
            <a href="index.php">
                <strong>StumpVision</strong> Admin
            </a>
        </div>
        <nav class="main-nav">
            <a href="index.php">Dashboard</a>
            <a href="matches.php">Matches</a>
            <a href="players.php">Players</a>
            <a href="stats.php">Stats</a>
            <a href="live-sessions.php">Live Sessions</a>
        </nav>
        <div class="user-menu">
            <span class="username"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>
</header>
