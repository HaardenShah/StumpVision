# StumpVision Admin Backend

Complete admin dashboard for managing matches, players, and statistics.

## Features

### 🔐 Authentication
- Secure session-based authentication
- Password hashing with bcrypt
- CSRF protection on all forms
- Session timeout handling

### 📊 Dashboard
- Overview statistics (matches, live sessions, players)
- Recent matches list
- Quick action buttons
- Real-time data

### 🏏 Match Management
- View all saved matches
- Match verification system (only verified matches count toward stats)
- Delete matches
- Detailed match viewer with:
  - Team information
  - Player lists
  - Match metadata
  - Verification status

### 👤 Player Registry
- Register official players
- Associate players with teams
- Only registered players' stats are tracked
- Delete players

**Security Feature**: This solves the problem of anyone being able to manipulate stats. Only verified matches with registered players count toward aggregate statistics.

### 📈 Aggregate Statistics
- Comprehensive batting stats:
  - Matches, Innings, Runs, Highest Score
  - Average, Strike Rate
  - Hundreds, Fifties, Fours, Sixes
- Bowling statistics:
  - Wickets, Runs, Balls
  - Economy Rate, Best Bowling
- **Only counts verified matches**
- **Only tracks registered players**

### 🔴 Live Session Management
- View all live score sharing sessions
- Stop active sessions
- Delete old sessions
- Direct links to live viewers

## Installation

### 1. Default Credentials
```
Username: admin
Password: changeme
```

**⚠️ IMPORTANT: Change these immediately!**

### 2. Change Password

Edit `admin/auth.php` and update:

```php
define('ADMIN_USERNAME', 'your_username');
define('ADMIN_PASSWORD_HASH', password_hash('your_secure_password', PASSWORD_BCRYPT));
```

### 3. Access
Navigate to: `http://your-domain.com/admin/`

You'll be redirected to the login page.

## File Structure

```
admin/
├── auth.php              # Authentication system
├── index.php             # Dashboard
├── login.php             # Login page
├── logout.php            # Logout handler
├── matches.php           # Match management
├── players.php           # Player registry
├── stats.php             # Aggregate statistics
├── live-sessions.php     # Live session management
├── header.php            # Shared header
├── styles.css            # Admin styling
└── README.md             # This file
```

## How It Works

### Player Stat Tracking

**Problem**: Anyone can visit the site and create a match with any player name, which would corrupt statistics.

**Solution**:
1. Admin registers official players in the **Player Registry**
2. Matches are scored normally by users
3. Admin reviews matches and **verifies** them
4. Only verified matches with registered players count toward stats
5. Aggregate stats are calculated from verified matches only

### Workflow

```
User scores match
    ↓
Match saved to /data/*.json
    ↓
Admin logs in to dashboard
    ↓
Reviews match in Match Management
    ↓
Verifies match (if legitimate)
    ↓
Stats automatically updated in Stats page
```

## API Endpoints

### Player Registry API (`api/players.php`)

**Public Endpoints:**
- `GET ?action=list` - Get all registered players
- `GET ?action=get&id=<player_id>` - Get specific player

**Admin-Only Endpoints:**
- `POST ?action=add` - Register new player
  ```json
  {
    "name": "Player Name",
    "team": "Team Name"
  }
  ```
- `POST ?action=update` - Update player info
  ```json
  {
    "id": "playername",
    "name": "Updated Name",
    "team": "Updated Team"
  }
  ```
- `POST ?action=delete` - Delete player
  ```json
  {
    "id": "playername"
  }
  ```

## Security Features

- ✅ Session-based authentication
- ✅ Password hashing (bcrypt)
- ✅ CSRF token protection
- ✅ Input sanitization
- ✅ XSS prevention
- ✅ Admin-only API endpoints
- ✅ Secure password storage

## Customization

### Changing Colors

Edit `styles.css` and modify the `:root` variables:

```css
:root {
  --bg: #0b1120;
  --card: #1e293b;
  --accent: #0ea5e9;
  /* ... */
}
```

### Adding More Admins

Currently supports single admin. To add multiple admins:

1. Create an `admins.json` file
2. Modify `auth.php` to check against the file
3. Add user management UI

## Maintenance

### Backup Data

Regularly backup:
- `/data/*.json` - All matches
- `/data/players.json` - Player registry
- `/data/live/*.json` - Live sessions

### Cleanup

Old live sessions can accumulate. Use the Live Sessions page to delete inactive sessions.

## Troubleshooting

### Can't Login
- Check that session_start() works on your server
- Verify PHP version (7.4+)
- Check file permissions on `/admin/` directory

### Stats Not Showing
- Ensure matches are verified (green badge)
- Ensure players are registered in Player Registry
- Player names must match exactly (case-insensitive)

### Live Sessions Not Working
- Check that `/data/live/` directory exists
- Verify write permissions
- Ensure `LIVE_SCORE_ENABLED = true` in `api/live.php`

## Future Enhancements

Potential features to add:
- Multi-user admin system
- Email notifications for new matches
- Bulk match verification
- Export stats to CSV/PDF
- Player profiles with photos
- Match editing capabilities
- Team management system
- Tournament management

## Support

For issues or questions, check the main StumpVision README or create an issue on GitHub.

---

**Built with ❤️ for cricket scoring**
