# 🏏 StumpVision v2 — Cricket Scorer

**StumpVision** is a lightweight, mobile-first web app for scoring cricket matches. Built with **PHP + vanilla JavaScript**, it works completely offline, installs as a PWA, and provides **real-time live score sharing** for spectators.

Perfect for pickup cricket, club matches, and growing your cricket community! 🌟

---

## ⚡ Features

### Core Scoring
✅ **Simple scoring pad** - Record runs, boundaries, wickets, extras with one tap
✅ **Wicket type tracking** - Record dismissal type (bowled, caught, LBW, stumped, run out, hit wicket)
✅ **Smart run-out logic** - Track runs completed and which batsman got out
✅ **Smart cricket logic** - No-balls trigger free hits, auto-advance overs after 6 legal balls
✅ **Extras tracking** - NB, WD, Byes, Leg Byes tracked separately with proper wide ball handling
✅ **Comprehensive stats** - Strike rates, economy rates, balls faced, overs bowled, dot balls, maidens
✅ **Ball-by-ball tracking** - Complete delivery history with undo support
✅ **Target tracker** - Real-time chase calculations in 2nd innings
✅ **Partnership tracking** - Monitor current and historical partnerships with runs and balls
✅ **Fall of wickets** - Track when and how each wicket fell
✅ **Milestones** - Automatic detection of 50s, 100s, 150s, 200s

### Match Management
✅ **Auto-save on first ball** - Generates unique match ID and saves automatically
✅ **Auto-save per over** - Match data saved every 6 balls with throttling
✅ **Mid-innings player management** - Add, remove, or retire players during the match
✅ **Dynamic match settings** - Adjust overs and wickets limit on the fly
✅ **Retire/unretire batsmen** - Players can retire and return later with stats intact
✅ **Player stats retention** - Retired players keep their scores when they return
✅ **Match completion redirect** - Automatic redirect to summary page when match ends

### Live Score Sharing
✅ **Live score broadcast** - Share real-time scores with spectators via unique URL
✅ **Beautiful live viewer** - Gradient score cards with live badge animation
✅ **Auto-refresh** - Updates every 5 seconds for spectators
✅ **Full statistics display** - Batting, bowling, partnerships visible to viewers
✅ **Mobile optimized** - Clean, responsive layout for spectators
✅ **Session management** - Start/stop live sharing from scoring interface

### Admin Panel
✅ **Password-protected dashboard** - Secure admin access with session management
✅ **Match management** - View, verify, and delete saved matches
✅ **Match verification** - Mark matches as verified for stats counting
✅ **Player database** - Track all players across matches
✅ **Statistics overview** - Total matches, players, verified matches
✅ **Live session monitoring** - View and manage active live score sessions
✅ **Settings management** - Configure admin password and system settings

### Match Setup
✅ **Toss configuration** - Select who won toss and batting/bowling choice
✅ **Opening players** - Choose opening batsmen and bowler
✅ **Team rosters** - Add players dynamically with instant validation
✅ **Match settings** - Overs per innings, wickets limit, match format

### Mobile Experience
✅ **Sunlight-optimized UI** - High contrast white text on dark buttons for outdoor visibility
✅ **Auto dark mode** - Respects system preference
✅ **Haptic feedback** - Vibration on scoring actions
✅ **Touch-optimized** - Large buttons (48px+ tap targets)
✅ **PWA installable** - Add to home screen, works like native app
✅ **Offline-first** - Score matches without internet
✅ **Smart UI** - Scoring dock hidden on Stats/Settings for full content visibility
✅ **Fullscreen mode** - True fullscreen on mobile devices
✅ **Plain text output** - No Unicode characters for maximum compatibility  

---

## 🚀 Quick Start

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
- **Proper file ownership** - `/data/` directory owned by web server user
- **Write permissions** - 755 on directories (owner can write, others read-only)
- **Session support** - For admin panel authentication
- **JSON support** - Standard in PHP 7.4+

---

## 🎮 How to Use

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

## 🎯 Scoring Rules

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

## 📊 Enhanced Statistics

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

## 🏗️ Technical Details

### Stack
- **Frontend**: Vanilla JavaScript (ES6+), HTML5, CSS3
- **Backend**: PHP 7.4+ with flat-file JSON storage
- **Storage**: localStorage (client) + `/data/*.json` (server)
- **Authentication**: PHP sessions for admin panel
- **Live Updates**: AJAX polling (5-second intervals)
- **Offline**: Service Worker + Cache API

### File Structure
```
Core App:
├── index.php          - Main scoring interface (all-in-one: HTML + CSS + JS)
├── setup.php          - Match configuration page
├── live.php           - Live score viewer for spectators
├── summary.php        - Match summary/recap page

Admin Panel:
├── admin/
│   ├── index.php         - Dashboard with stats overview
│   ├── login.php         - Authentication page
│   ├── matches.php       - Match management & verification
│   ├── players.php       - Player database & statistics
│   ├── live-sessions.php - Active live session monitoring
│   ├── settings.php      - System configuration
│   ├── auth.php          - Authentication logic
│   └── header.php        - Shared admin navigation

Backend API:
├── api/
│   ├── matches.php    - CRUD for match data with CSRF protection
│   ├── live.php       - Live session management & updates
│   ├── players.php    - Player data aggregation
│   └── renderCard.php - Share card generation (if available)

PWA:
├── manifest.webmanifest - App metadata for installation
└── service-worker.js    - Offline caching strategy

Assets:
├── assets/
│   └── icons/         - PWA icons (192x192, 512x512)

Data Storage:
├── data/
│   ├── *.json         - Saved match files (auto-generated)
│   └── live/          - Live session state files

Configuration (Secure):
├── config/
│   ├── config.json         - Admin settings with password hash (600 perms)
│   ├── config.example.json - Template configuration file
│   └── .gitignore          - Prevents committing password hash
```

---

## 🔧 Customization

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

## 🐛 Troubleshooting

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

## 🔐 Security

The app implements several security measures:

- ✅ **CSRF Protection** - Token validation on all mutation endpoints
- ✅ **Rate Limiting** - 60 requests/minute per IP (120/min for live updates)
- ✅ **Input Sanitization** - ID validation and path traversal prevention
- ✅ **Password Hashing** - bcrypt for admin authentication
- ✅ **Session Management** - PHP sessions for admin access control
- ✅ **File-based Storage** - No SQL injection risk
- ✅ **Security Headers** - Implemented in all API responses

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

## 🏏 Perfect For

- **Cricket clubs** - Track all matches with admin panel and player stats
- **Pickup matches** - Easy setup, score, and share with live viewer
- **Tournaments** - Verify matches, manage player database
- **Spectators** - Share live score URL for real-time updates
- **Remote viewing** - Friends and family can watch scores live
- **Players who arrive late** - Add them mid-match seamlessly
- **Flexible team sizes** - Manage players dynamically
- **Match archives** - All matches saved with verification system

**Share live scores, build your cricket community!** 🚀📱

---

## 📄 License

Open source - use it, modify it, share it! 

Built with ❤️ for the cricket community.

---

## 👨‍💻 Credits

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

## 🆘 Support

Need help? Check:
1. `DEPLOYMENT.md` - Detailed setup guide
2. `ICONS_README.md` - Icon creation guide
3. `CODE_REVIEW.md` - Known issues and fixes (if available)
4. PHP error logs: `/var/log/php_errors.log`
5. Browser console for JavaScript errors

---

## 📝 Changelog

### v2.2 (Latest - November 2024)
- ✨ **Live Score Sharing** - Real-time score viewer for spectators with auto-refresh
- ✨ **Admin Panel** - Complete match and player management system
- ✨ **Match Verification** - Mark matches as verified for official stats
- ✨ **Auto-save System** - Saves on first ball, then every over
- ✨ **Player Database** - Track player statistics across all matches
- ✨ **Live Session Monitoring** - Admin view of active live sessions
- 🐛 **Fixed match persistence** - Corrected data directory permissions
- 🐛 **Fixed match completion** - Automatic redirect to summary page
- 🐛 **Fixed Unicode display** - Replaced all Unicode with plain text
- 🐛 **Fixed last wicket update** - Proper handling of final wicket
- 🔒 **CRITICAL SECURITY FIX** - Moved config with password hash to separate directory with 600 permissions
- 🔒 **Enhanced Security** - CSRF protection, rate limiting, session management, secure file permissions
- 🔒 **Permission Hardening** - Replaced 777 with proper ownership-based security (755 data, 750 config)
- 📱 **Improved mobile UX** - Better live viewer layout

### v2.1
- ✨ Added wicket type tracking (6 dismissal types)
- ✨ Smart run-out logic with runs and batsman selection
- ✨ Mid-innings player management (add/remove/retire)
- ✨ Dynamic overs and wickets adjustment
- ✨ Comprehensive stats: partnerships, fall of wickets, milestones
- ✨ Bowling analytics: dot balls and maidens tracking
- 🎨 Improved sunlight visibility with white text on modals
- 🎨 Smart UI: scoring dock hidden on Stats/Settings tabs
- 🐛 Fixed wide ball logic (1 wide + additional runs)
- 🐛 Fixed free hit + run out interaction
- 📱 True fullscreen mobile support

### v2.0
- Initial release with core scoring features
- PWA support and offline functionality
- Basic match statistics

---

*StumpVision v2.2 - Score fast. Share live. Play cricket.* 🏏