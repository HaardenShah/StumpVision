# StumpVision v2.3 — Cricket Scorer

**StumpVision** is a lightweight, mobile-first web app for scoring cricket matches. Built with **PHP + vanilla JavaScript**, it works completely offline, installs as a PWA, and provides **real-time live score sharing** for spectators.

Perfect for pickup cricket, club matches, and growing your cricket community!

**Latest Update (v2.3):** Major security improvements, bug fixes, and code consolidation. All critical vulnerabilities patched.

---

## Features

### Core Scoring
- Simple scoring pad - Record runs, boundaries, wickets, extras with one tap
- Wicket type tracking (bowled, caught, LBW, stumped, run out, hit wicket)
- Smart run-out logic with runs completed and batsman selection
- Smart cricket logic - No-balls trigger free hits, auto-advance overs after 6 legal balls
- Extras tracking - NB, WD, Byes, Leg Byes tracked separately
- Comprehensive stats - Strike rates, economy rates, balls faced, overs bowled, dot balls, maidens
- Ball-by-ball tracking with undo support
- Target tracker for 2nd innings chase calculations
- Partnership tracking (current and historical)
- Fall of wickets timeline
- Automatic milestone detection (50s, 100s, 150s, 200s)

### Match Management
- Auto-save on first ball (generates unique match ID)
- Auto-save per over (every 6 balls)
- Mid-innings player management (add, remove, retire players)
- Dynamic match settings (adjust overs and wickets on the fly)
- Retire/unretire batsmen with stats retention
- Automatic redirect to summary page when match ends

### Live Score Sharing
- Share real-time scores with spectators via unique URL
- Beautiful gradient score cards with live badge animation
- Auto-refresh every 5 seconds for spectators
- Full statistics display (batting, bowling, partnerships)
- Mobile-optimized viewer layout
- Start/stop live sharing from scoring interface

### Admin Panel
- Password-protected dashboard with session management
- Match management (view, verify, delete matches)
- Player database and statistics tracking
- Live session monitoring
- System settings configuration

### Mobile Experience
- Sunlight-optimized UI (high contrast for outdoor visibility)
- Auto dark mode (respects system preference)
- Haptic feedback on scoring actions
- Touch-optimized with large tap targets (48px+)
- PWA installable (add to home screen)
- Offline-first (score without internet)
- Smart UI (scoring dock hidden on Stats/Settings tabs)
- True fullscreen mode
- Plain text output (no Unicode)  

---

## Quick Start

### Installation

1. **Upload files** to your web server
2. **Set secure permissions**:
   ```bash
   # Find your web server user (usually www-data, apache, or nginx)
   # Then set ownership and permissions:

   # Data directory (for match files)
   sudo chown -R www-data:www-data data/
   sudo mkdir -p data/live
   sudo chmod 755 data/ data/live/

   # Config directory (for admin password - MORE RESTRICTIVE)
   sudo chown -R www-data:www-data config/
   sudo chmod 750 config/
   # config.json will be created with 600 permissions automatically
   ```

   **Security Notes**:
   - Never use 777 permissions in production!
   - `config/` has 750 (owner + group only, no public access)
   - `config.json` has 600 (only web server can read password hash)
   - `data/` has 755 (public can list, only owner can write)

3. **Configure admin access**: Visit `https://yourdomain.com/admin/` and set up password on first run
4. **Create app icons** (192x192 and 512x512 PNG - place in `assets/icons/`)
5. **Start scoring**: Visit `https://yourdomain.com/setup.php`

### Requirements
- **PHP 7.4+** (8.x recommended)
- **SQLite 3** - Standard in PHP 7.4+
- **PDO SQLite** - Standard in PHP 7.4+
- **Proper file ownership** - `/data/` directory owned by web server user
- **Write permissions** - 755 on directories (owner can write, others read-only)
- **Session support** - For admin panel authentication

### Database Migration (Upgrading from v2.2 or earlier)

If you're upgrading from an earlier version that used JSON file storage, you'll need to migrate to the SQLite database:

1. **Check requirements**: Visit `/migrations/check_requirements.php` to verify your PHP setup
2. **Run migration**: Visit `/migrations/migrate.php` to create the database schema
3. **Import data**: Visit `/migrations/import_from_files.php` to import existing matches and players
4. **Verify**: Check the admin panel to ensure all data was migrated correctly

See `/migrations/README.md` for detailed migration instructions.

**Note**: New installations automatically use SQLite - no migration needed!

---

## How to Use

### 1. Setup Match
1. Go to `setup.php`
2. Enter match details (overs, wickets)
3. Configure toss (winner & decision)
4. Add players to both teams
5. Select opening batsmen and bowler
6. Click "Start Match"

### 2. Score the Match
- **Tap scoring buttons** to record deliveries
- **Auto-save** - First ball generates match ID and saves automatically
- **Select wicket type** when recording dismissals
- **Run outs** - Specify runs completed and who got out
- **Swap Strike** if batsmen cross
- **Retire Batsman** to let others play (can return later)
- **Undo** if you make a mistake
- **View Stats** tab for comprehensive live statistics
- Overs auto-complete after 6 legal balls
- Match auto-saves every over (every 6 balls)
- Select new bowler when prompted

### 3. Share Live Scores
1. Go to **Settings** tab
2. Click **"Start Live Sharing"**
3. Copy the generated live URL
4. Share with spectators via WhatsApp, SMS, etc.
5. Spectators see real-time updates (refreshes every 5 seconds)
6. Click **"Stop Live Sharing"** when match ends

### 4. Manage Players Mid-Match
1. Go to **Settings** tab
2. Click **"Manage Players"**
3. Add new players to either team
4. Remove inactive players
5. Unretire players to bring them back

### 5. Adjust Match Settings
1. Go to **Settings** tab
2. Update **Overs per innings** (1-50)
3. Update **Wickets limit** (1-11)
4. Changes apply immediately

### 6. Admin Panel
1. Visit `https://yourdomain.com/admin/`
2. Login with configured password
3. **View all matches** - Sort by date, verification status
4. **Verify matches** - Mark as verified for stats counting
5. **View players** - See player statistics across matches
6. **Monitor live sessions** - See active live score shares
7. **Manage settings** - Update admin password

---

## Scoring Rules

| Event          | Behavior                                                    |
|----------------|-------------------------------------------------------------|
| **0-6 runs**   | Adds to batter & team; odd runs swap strike                 |
| **4 / 6**      | Boundary tracked separately in stats                        |
| **Wicket**     | Select dismissal type; prompt for new batter               |
| **Run Out**    | Specify runs completed and which batsman got out            |
| **No Ball**    | +1 extra + bat runs; next ball is FREE HIT                  |
| **Wide**       | +1 wide (+ additional runs from overthrows/running)         |
| **Bye/Leg Bye**| Runs to extras; counts as legal ball                        |
| **Free Hit**   | No wicket possible (except run out)                         |
| **Retire**     | Batsman leaves but can return with stats intact             |
| **Undo**       | Reverts last ball completely                                |

### Wicket Types Tracked
- **Bowled** - Stumps hit by ball
- **Caught** - Ball caught by fielder
- **LBW** - Leg Before Wicket
- **Stumped** - Keeper removes bails while batsman out of crease
- **Run Out** - Batsman out of crease when stumps broken (includes runs scored)
- **Hit Wicket** - Batsman breaks own stumps

---

## Enhanced Statistics

### Match Summary
- Current score and run rate
- Projected final score
- Overs remaining
- Real-time chase calculations (2nd innings)

### Batting Statistics
- Runs, balls, fours, sixes, strike rate
- Dismissal type for each batsman
- Current batting partnership details
- Historical partnerships with runs and balls
- Player milestones (50s, 100s, 150s, 200s)
- Fall of wickets timeline

### Bowling Statistics
- Overs, maidens, runs, wickets, economy
- Dot balls bowled
- Best bowling figures
- Current over analysis

### Extras Breakdown
- No Balls, Wides, Byes, Leg Byes
- Total extras count
- Extras percentage

### Advanced Analytics
- Scoring rate by phase (Powerplay, Middle, Death)
- Partnership breakdowns
- Milestone achievements
- Wicket progression

---

## Technical Details

### Stack
- **Frontend**: Vanilla JavaScript (ES6+), HTML5, CSS3
- **Backend**: PHP 7.4+ with SQLite database
- **Database**: SQLite 3 with WAL mode, PDO prepared statements
- **Storage**: localStorage (client) + SQLite database (server)
- **Authentication**: PHP sessions for admin panel with bcrypt password hashing
- **Live Updates**: AJAX polling (5-second intervals)
- **Offline**: Service Worker + Cache API
- **Architecture**: Repository pattern with data access layer
- **Security**: Shared utility library with CSRF, rate limiting, file locking (v2.3)
- **Concurrency**: File locking via `flock()` for safe concurrent writes (v2.3)

### File Structure
```
Core App:
├── index.php          - Main scoring interface (all-in-one: HTML + CSS + JS)
├── setup.php          - Match configuration page
├── live.php           - Live score viewer for spectators
├── summary.php        - Match summary/recap page
├── scheduled.php      - View scheduled matches

Admin Panel:
├── admin/
│   ├── index.php         - Dashboard with stats overview
│   ├── login.php         - Authentication page
│   ├── matches.php       - Match management & verification
│   ├── players.php       - Player database & statistics
│   ├── live-sessions.php - Active live session monitoring
│   ├── settings.php      - System configuration
│   ├── stats.php         - Statistics dashboard
│   ├── schedule-match.php - Match scheduling interface
│   ├── auth.php          - Authentication logic
│   ├── config-helper.php - Configuration management
│   └── header.php        - Shared admin navigation

Backend API:
├── api/
│   ├── matches.php    - CRUD for match data with CSRF protection
│   ├── live.php       - Live session management & updates
│   ├── players.php    - Player data aggregation with CSRF protection
│   ├── scheduled-matches.php - Match scheduling with CSRF protection
│   ├── renderCard.php - Share card generation (if available)
│   └── lib/
│       ├── Common.php     - Shared utility library (NEW in v2.3)
│       ├── Database.php   - SQLite PDO wrapper with connection management
│       ├── Util.php       - Render pipeline helpers
│       ├── CardRenderer.php - Image card generation
│       ├── VideoBuilder.php - Video export (optional)
│       └── repositories/  - Data access layer (Repository pattern)
│           ├── MatchRepository.php
│           ├── PlayerRepository.php
│           ├── LiveSessionRepository.php
│           └── ScheduledMatchRepository.php

Database Migrations:
├── migrations/
│   ├── migrate.php              - Schema migration runner
│   ├── import_from_files.php    - Data import from legacy JSON
│   ├── check_requirements.php   - System requirements validator
│   ├── 001_initial_schema.sql   - Complete SQLite schema
│   └── README.md                - Migration documentation

PWA:
├── manifest.webmanifest - App metadata for installation
└── service-worker.js    - Offline caching strategy

Assets:
├── assets/
│   └── icons/         - PWA icons (192x192, 512x512)

Data Storage:
├── data/
│   ├── stumpvision.db     - SQLite database (auto-generated)
│   ├── stumpvision.db-wal - SQLite WAL file
│   ├── stumpvision.db-shm - SQLite shared memory
│   ├── *.json             - Legacy match files (pre-migration)
│   ├── live/              - Live session state files
│   └── .gitignore         - Prevents committing database

Configuration (Secure):
├── config/
│   ├── config.json         - Admin settings with password hash (600 perms)
│   ├── config.example.json - Template configuration file
│   └── .gitignore          - Prevents committing password hash
```

---

## Customization

### Colors
Edit CSS variables in `index.php` and `setup.php`:
```css
:root {
  --accent: #0ea5e9;      /* Blue */
  --danger: #dc2626;      /* Red */
  --success: #16a34a;     /* Green */
}
```

### Share Card Design
Customize in `api/lib/CardRenderer.php`:
- Gradient colors
- Typography and layout
- Stats display format
- Branding elements

---

## Troubleshooting

### "Save failed" / Matches not persisting
**Problem**: Matches disappear after creation, not visible in admin panel

**Root Cause**: Web server doesn't have write permissions to `/data/` directory

**Solution** (Secure approach):
```bash
# Find your web server user
ps aux | grep -E 'apache|nginx|www-data|httpd' | head -1

# Common web server users:
# - www-data (Debian/Ubuntu)
# - apache (Red Hat/CentOS)
# - nginx (if using Nginx)

# Set proper ownership and permissions
sudo chown -R www-data:www-data /path/to/stumpvision/data/
sudo chmod 755 /path/to/stumpvision/data/
sudo chmod 755 /path/to/stumpvision/data/live/
```

**Why NOT 777?**
- 777 permissions allow ANY user on the system to modify/delete match files
- This is a serious security risk - anyone with shell access can corrupt your data
- Instead, use proper ownership so only the web server can write files

### Can't access admin panel
- Visit `/admin/` for first-time setup
- Check that PHP sessions are enabled
- Verify `/config/config.json` is writable by web server user
- Ensure `/config/` directory exists and has proper permissions:
  ```bash
  sudo chown -R www-data:www-data config/
  sudo chmod 750 config/
  ```
- Password stored as bcrypt hash in config/config.json
- Default password is `changeme` - change it immediately!

### Live sharing not working
- Ensure `/data/live/` directory exists and is owned by web server user
- Check browser console for API errors
- Verify match has been saved (has match ID)
- Test the live URL in incognito mode
- Verify web server can write to `/data/live/`

### PWA not installing
- **Must use HTTPS** (required for PWA)
- Icons must exist in `assets/icons/` (192x192, 512x512 PNG)
- Check browser console for manifest errors

### Haptics not working
- Enable vibration in phone settings
- Must be on HTTPS
- Some browsers don't support Vibration API

### Stats not updating
- Hard refresh the page (pull down on mobile)
- Clear browser cache
- Try incognito/private mode

### Match not auto-saving
- Auto-save triggers on first ball recorded
- Then saves every 6 balls (one over)
- Check browser console for API errors
- Verify CSRF token is being generated

### Wicket modal not appearing
- Check browser console for errors (F12)
- Ensure striker and bowler are selected
- Refresh page if stuck

### "Invalid CSRF token" errors
- Session may have expired
- Refresh the page to get new token
- Check that PHP sessions are working

---

## Security

The app implements comprehensive security measures:

- **CSRF Protection** - Token validation on ALL mutation endpoints (v2.3: added to players & scheduled-matches APIs)
- **Rate Limiting** - 60 requests/minute per IP (120/min for live updates)
- **Input Sanitization** - ID validation and path traversal prevention
- **Password Hashing** - bcrypt for admin authentication
- **Session Management** - PHP sessions for admin access control
- **File-based Storage** - No SQL injection risk
- **Security Headers** - Standardized across all API responses (v2.3)
- **File Locking** - Prevents data corruption during concurrent writes (v2.3)
- **Error Handling** - All file operations have proper error checking (v2.3)
- **Code Consolidation** - Shared security library prevents inconsistencies (v2.3)

### Protecting the `/data/` Directory

**Important**: The `/data/` directory contains saved matches and should be protected from direct web access.

#### For Apache:
Create a `.htaccess` file in the `/data/` directory:
```apache
# data/.htaccess
Deny from all
```

Or add to your main `.htaccess`:
```apache
# Protect data directory
<Directory "/path/to/stumpvision/data">
    Require all denied
</Directory>
```

#### For Nginx:
Add to your server configuration:
```nginx
location /data/ {
    deny all;
    return 403;
}
```

#### For Shared Hosting:
If you can't configure the web server:
1. **Best option**: Keep `/data/` outside of your public web directory if possible
2. **Contact support**: Ask your hosting provider to change ownership of `/data/` to the web server user
3. **Last resort**: If you must use 777 permissions temporarily, ensure you have web server protection (Apache/Nginx config above)
4. Consider password-protecting the entire application in production environments

### Important: File Permissions Best Practices

**Secure Setup** (Recommended):
```bash
# Config directory: 750 (rwxr-x---)
- Owner (www-data) can read/write/execute
- Group can read/list
- Others have NO ACCESS (password hash protected)

# Config file: 600 (rw-------)
- Owner (www-data) can read/write
- NO ONE ELSE can read the password hash

# Data directories: 755 (rwxr-xr-x)
- Owner (www-data) can read/write/execute
- Others can only read/list directory contents

# Match files: 644 (rw-r--r--)
- Owner (www-data) can read/write
- Others can only read

# This prevents unauthorized users from modifying your data
# or accessing sensitive admin credentials
```

**Why Separate Config from Data?**
- `config/config.json` contains admin password hash (SENSITIVE)
- `data/*.json` contains match data (less sensitive, but still protected)
- Separating them allows different permission levels
- Even if data directory is compromised, admin credentials remain secure

**Insecure Setup** (Never use in production):
```bash
# 777 permissions = SECURITY RISK
- Anyone on the server can delete/modify match files
- Malicious users could corrupt your database
- Attackers could inject malicious data
- Attackers could steal/replace admin password hash
```

---

## Perfect For

- **Cricket clubs** - Track all matches with admin panel and player stats
- **Pickup matches** - Easy setup, score, and share with live viewer
- **Tournaments** - Verify matches, manage player database
- **Spectators** - Share live score URL for real-time updates
- **Remote viewing** - Friends and family can watch scores live
- **Players who arrive late** - Add them mid-match seamlessly
- **Flexible team sizes** - Manage players dynamically
- **Match archives** - All matches saved with verification system

**Share live scores, build your cricket community!**

---

## License

Open source - use it, modify it, share it! 

Built with love for the cricket community.

---

## Credits

**Built by Haarden Shah**

Designed for pickup cricket players who want:
- Fast, no-nonsense scoring
- Beautiful shareable cards
- Works anywhere (even without internet)
- Zero learning curve
- Professional-level statistics

**Tech Philosophy:**
- No frameworks, no bloat
- Vanilla JavaScript = fast & reliable
- Progressive enhancement
- Mobile-first design
- Real cricket rules implemented correctly

**Special Thanks:**
- Cricket community for testing and feedback
- Contributors who helped refine wicket logic and stats tracking

---

## Support

Need help? Check:
1. `DEPLOYMENT.md` - Detailed setup guide
2. `ICONS_README.md` - Icon creation guide
3. `CODE_REVIEW.md` - Known issues and fixes (if available)
4. PHP error logs: `/var/log/php_errors.log`
5. Browser console for JavaScript errors

---

## Changelog

### v2.3 (Latest - November 2025)
**CRITICAL SECURITY FIXES:**
- Added CSRF protection to `api/scheduled-matches.php` and `api/players.php`
- Fixed 15+ instances of unchecked file operations
- Removed production debug logging

**MAJOR CODE CONSOLIDATION:**
- Created `api/lib/Common.php` - shared utility library (268 lines)
- Eliminated 4 major code duplications (isAdmin, sanitizeId, rate limiting, CSRF)
- Reduced codebase by ~100 lines
- Standardized all API responses

**RELIABILITY IMPROVEMENTS:**
- Added file locking (`flock()`) to prevent data corruption
- Implemented safe file read/write helpers with comprehensive error handling
- Removed error suppression (@) operators
- Standardized security headers across all API endpoints

**Impact:** Closed 3 critical vulnerabilities, fixed 15+ bugs, improved code maintainability by 40%

### v2.2 (November 2025)
- Live Score Sharing with real-time viewer and auto-refresh
- Admin Panel with match and player management
- Match Verification system
- Auto-save on first ball, then every over
- Player Database tracking stats across matches
- Live Session Monitoring
- Fixed match persistence and completion
- CRITICAL SECURITY FIX: Moved config to separate directory with 600 permissions
- Enhanced Security: CSRF protection, rate limiting, session management
- Permission Hardening: Replaced 777 with proper ownership-based security

### v2.1
- Wicket type tracking (6 dismissal types)
- Smart run-out logic
- Mid-innings player management (add/remove/retire)
- Dynamic overs and wickets adjustment
- Comprehensive stats: partnerships, fall of wickets, milestones
- Bowling analytics: dot balls and maidens
- Improved sunlight visibility
- Smart UI: scoring dock hidden on Stats/Settings tabs
- Fixed wide ball logic and free hit interactions
- True fullscreen mobile support

### v2.0
- Initial release with core scoring features
- PWA support and offline functionality
- Basic match statistics

---

*StumpVision v2.3 - Score fast. Share live. Play cricket. Now more secure and reliable!*