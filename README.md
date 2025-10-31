# 🏏 StumpVision v2 — Cricket Scorer

**StumpVision** is a lightweight, mobile-first web app for scoring cricket matches. Built with **PHP + vanilla JavaScript**, it works completely offline, installs as a PWA, and generates **beautiful shareable scorecards** for social media.

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
✅ **Mid-innings player management** - Add, remove, or retire players during the match  
✅ **Dynamic match settings** - Adjust overs and wickets limit on the fly  
✅ **Retire/unretire batsmen** - Players can retire and return later with stats intact  
✅ **Player stats retention** - Retired players keep their scores when they return  

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

### Social Sharing
✅ **Save to server** - Persistent match storage with unique IDs
✅ **Premium share cards** - Beautiful gradient scorecards with modern design
✅ **Social media ready** - One-tap share to Instagram, WhatsApp, Twitter
✅ **Live score sharing** - Generate shareable links for real-time score viewing (optional)

### Admin Backend
✅ **Match management** - Review, verify, and delete matches
✅ **Player registry** - Register official players to track career statistics
✅ **Aggregate stats** - Career statistics across all verified matches
✅ **Live session control** - Manage active live score sharing sessions
✅ **Verification system** - Only verified matches count toward player stats

### Security & Accessibility
✅ **CSRF protection** - Token-based security on all state-changing operations
✅ **Rate limiting** - Prevent API abuse (60 req/min)
✅ **XSS prevention** - Safe DOM manipulation throughout
✅ **Screen reader support** - Full ARIA labels and live regions
✅ **Keyboard navigation** - ESC to close modals, focus management
✅ **Toast notifications** - Modern, accessible notifications instead of alerts

---

## 🚀 Quick Start

### Installation

1. **Upload files** to your web server
2. **Set permissions**: `chmod 755 data data/cards data/live`
3. **Create app icons** (192x192 and 512x512 PNG - see ICONS_README.md)
4. **Configure admin** (see Admin Setup below)
5. **Visit**: `https://yourdomain.com/setup.php`

### Requirements
- **PHP 7.4+** (8.x recommended)
- **Write permissions** on `/data/` directory
- **ImageMagick extension** (for share cards)
- **FFmpeg** (optional - for video cards)
- **Session support** (for admin authentication)

### Admin Setup

The admin backend is included but requires password configuration:

1. **Edit** `admin/auth.php`
2. **Change default credentials**:
   ```php
   define('ADMIN_USERNAME', 'your_username');
   define('ADMIN_PASSWORD_HASH', password_hash('your_secure_password', PASSWORD_BCRYPT));
   ```
3. **Access admin panel**: `https://yourdomain.com/admin/`
4. **Default credentials** (CHANGE IMMEDIATELY):
   - Username: `admin`
   - Password: `changeme`

See `admin/README.md` for full admin documentation.

---

## 🎨 Share Card Design

The share cards feature a **premium gradient design** inspired by modern travel apps:

- **Gradient backgrounds** - Eye-catching blue-to-purple gradients
- **Glassmorphism effects** - Frosted glass cards with subtle shadows
- **Clean typography** - Clear hierarchy with score emphasis
- **Team branding** - Prominent team names with modern layout
- **Stats showcase** - Top performers highlighted beautifully
- **Social-ready** - Optimized for Instagram Stories, Twitter, WhatsApp

Perfect for **growing your cricket club** through viral social sharing! 📱✨

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
- **Select wicket type** when recording dismissals
- **Run outs** - Specify runs completed and who got out
- **Swap Strike** if batsmen cross
- **Retire Batsman** to let others play (can return later)
- **Undo** if you make a mistake
- **View Stats** tab for comprehensive live statistics
- Overs auto-complete after 6 legal balls
- Select new bowler when prompted

### 3. Manage Players Mid-Match
1. Go to **Settings** tab
2. Click **"Manage Players"**
3. Add new players to either team
4. Remove inactive players
5. Unretire players to bring them back

### 4. Adjust Match Settings
1. Go to **Settings** tab
2. Update **Overs per innings** (1-50)
3. Update **Wickets limit** (1-11)
4. Changes apply immediately

### 5. Save & Share
1. Go to **Settings** tab
2. Click **"Save Match"** (generates unique ID)
3. Click **"Share Score Card"** (creates beautiful graphic)
4. Share to social media or download

### 6. Live Score Sharing (Optional)
1. Enable in `api/live.php`: Set `LIVE_SCORE_ENABLED = true`
2. Go to **Settings** tab
3. Click **"Start Live Sharing"**
4. Copy generated link
5. Share link with viewers
6. Scores update automatically every 5 seconds

Viewers can watch live at: `yourdomain.com/live.php?id=<session_id>`

### 7. Admin Management
1. Access admin panel: `yourdomain.com/admin/`
2. **Register Players** - Add official players to track career stats
3. **Review Matches** - View all saved matches
4. **Verify Matches** - Mark legitimate matches (only verified matches count toward stats)
5. **View Statistics** - See aggregate player statistics across all verified matches
6. **Manage Live Sessions** - Control active live score sharing

**Why verify matches?** Only verified matches with registered players count toward aggregate statistics. This prevents stat manipulation from random visitors.

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
- **Backend**: PHP 8.x with flat-file JSON storage
- **Storage**: localStorage (client) + `/data/*.json` (server)
- **Offline**: Service Worker + Cache API
- **Share Cards**: ImageMagick + modern gradient design
- **Authentication**: Session-based with bcrypt password hashing
- **Security**: CSRF protection, rate limiting, XSS prevention

### File Structure
```
Core App (v2):
├── index.php          - Main scoring app (HTML + CSS + JS)
├── setup.php          - Match setup (HTML + CSS + JS)
├── live.php           - Live score viewer (real-time updates)
└── summary.php        - Match summary view

Admin Backend:
├── admin/
│   ├── index.php          - Dashboard
│   ├── login.php          - Authentication
│   ├── matches.php        - Match management & verification
│   ├── players.php        - Player registry
│   ├── stats.php          - Aggregate statistics
│   ├── live-sessions.php  - Live session management
│   ├── auth.php           - Authentication system
│   ├── styles.css         - Admin UI styling
│   └── README.md          - Admin documentation

Backend APIs:
├── api/
│   ├── matches.php    - Match CRUD (with CSRF + rate limiting)
│   ├── players.php    - Player registry API
│   ├── live.php       - Live score sharing API
│   ├── renderCard.php - Share card generation
│   └── lib/           - Rendering libraries

PWA:
├── manifest.webmanifest - App metadata
└── service-worker.js    - Offline caching

Data Storage:
├── data/
│   ├── *.json         - Saved matches
│   ├── players.json   - Registered players
│   ├── live/          - Live session data
│   └── cards/         - Generated share cards (PNG/MP4)
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

### "Save failed"
- Check `/data/` has write permissions (755 or 777)
- Verify PHP error logs

### "Share failed"
- Save match first
- Check ImageMagick: `php -m | grep imagick`
- Verify `/data/cards/` exists and is writable

### PWA not installing
- **Must use HTTPS** (required for PWA)
- Icons must exist in `assets/icons/`

### Haptics not working
- Enable vibration in phone settings
- Must be on HTTPS

### Stats not updating
- Hard refresh the page (pull down on mobile)
- Clear browser cache
- Try incognito/private mode

### Wicket modal not appearing
- Check browser console for errors (F12)
- Ensure striker and bowler are selected
- Refresh page if stuck

---

## 🔐 Security

The app implements comprehensive security measures:

**API Security:**
- ✅ CSRF token protection on all POST requests
- ✅ Rate limiting (60 requests/min per IP)
- ✅ Input sanitization and validation
- ✅ XSS prevention with safe DOM manipulation
- ✅ Security headers (X-Frame-Options, CSP, etc.)

**Admin Security:**
- ✅ Session-based authentication
- ✅ Bcrypt password hashing
- ✅ Admin-only API endpoints
- ✅ CSRF protection on all admin forms
- ✅ Secure session timeout

**Data Protection:**
- ✅ File-based storage (no SQL injection risk)
- ✅ Player registry prevents stat manipulation
- ✅ Match verification system for data integrity

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
1. Keep `/data/` outside of your public web directory if possible
2. Or rely on PHP's built-in file permissions (755/644)
3. Consider adding password protection for production use

---

## 🏏 Perfect For

**Public Cricket:**
- **Cricket clubs** building community through social sharing
- **Pickup matches** in parks with instant scorecards
- **School/college tournaments** with shareable results
- **Social media content** that attracts new members
- **Growing cricket awareness** through viral sharing

**League & Tournament Management:**
- **Track player statistics** across entire seasons
- **Verify legitimate matches** to prevent stat manipulation
- **Aggregate career stats** for all registered players
- **Live score sharing** for spectators and families
- **Professional statistics** without expensive software

**Flexible Features:**
- **Players who arrive late** - add them mid-match seamlessly
- **Flexible team sizes** - manage players dynamically
- **Admin control** - full oversight of all matches and stats

**Share beautiful scorecards, track real stats, grow your club!** 🚀📱

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
1. `admin/README.md` - Admin backend documentation
2. `DEPLOYMENT.md` - Detailed setup guide
3. `ICONS_README.md` - Icon creation guide
4. `CODE_REVIEW.md` - Known issues and fixes (if available)
5. PHP error logs: `/var/log/php_errors.log`
6. Browser console for JavaScript errors

**Admin Issues:**
- Can't login? Check session support and file permissions
- Stats not showing? Ensure matches are verified and players registered
- Live sharing not working? Enable in `api/live.php`

---

## 📝 Changelog

### v2.2 (Latest)
**🔐 Security & Accessibility**
- ✨ Added CSRF token protection to all API endpoints
- ✨ Implemented rate limiting (60 requests/min)
- ✨ Fixed XSS vulnerabilities with safe DOM manipulation
- ✨ Added comprehensive ARIA labels for screen readers
- ✨ Implemented keyboard navigation (ESC to close modals)
- ✨ Added aria-live regions for score announcements
- 🎨 Modern toast notification system (replaced alerts)
- 🎨 Modal focus management and trapping

**👨‍💼 Admin Backend**
- ✨ Complete admin dashboard with authentication
- ✨ Match management interface (view, verify, delete)
- ✨ Player registry system to track official players
- ✨ Aggregate statistics across all verified matches
- ✨ Live session management interface
- ✨ Match verification system for stat integrity
- 🔒 Session-based auth with bcrypt password hashing

**🔴 Live Score Sharing**
- ✨ Real-time live score sharing with generated links
- ✨ Auto-updating live viewer page (3-second refresh)
- ✨ Beautiful gradient live score display
- ✨ Session management and ownership controls
- ⚙️ Disabled by default, easily enabled via config

**🐛 Bug Fixes**
- Fixed run-out batsman positioning logic
- Fixed partnership stats not updating for run-outs
- Improved cricket logic accuracy throughout

**📊 Player Stat Tracking**
- Only verified matches count toward stats
- Only registered players are tracked
- Prevents stat manipulation from public use
- Career statistics across all verified matches

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
- Premium share cards with gradient design
- Basic match statistics

---

*StumpVision v2.2 - Score fast. Share beautiful. Track stats. Grow your club.* 🏏