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

---

## 🚀 Quick Start

### Installation

1. **Upload files** to your web server
2. **Set permissions**: `chmod 755 data data/cards`
3. **Create app icons** (192x192 and 512x512 PNG - see ICONS_README.md)
4. **Visit**: `https://yourdomain.com/setup.php`

### Requirements
- **PHP 7.4+** (8.x recommended)
- **Write permissions** on `/data/` directory
- **ImageMagick extension** (for share cards)
- **FFmpeg** (optional - for video cards)

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

### File Structure
```
Core App (v2):
├── index.php          - Main app (all-in-one: HTML + CSS + JS)
├── setup.php          - Match setup (all-in-one: HTML + CSS + JS)

Backend API:
├── api/matches.php    - CRUD for match data
├── api/renderCard.php - Generate premium share graphics
└── api/lib/           - Image rendering with modern design

PWA:
├── manifest.webmanifest - App metadata
└── service-worker.js    - Offline caching

Data:
├── data/*.json        - Saved matches
└── data/cards/        - Generated share cards (PNG/MP4)
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

The app implements several security measures:

- ✅ Input sanitization on save
- ✅ File-based storage (no SQL injection risk)
- ✅ Security headers in API responses

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

- **Cricket clubs** building community through social sharing
- **Pickup matches** in parks with instant scorecards
- **School/college tournaments** with shareable results
- **Social media content** that attracts new members
- **Growing cricket awareness** through viral sharing
- **Players who arrive late** - add them mid-match seamlessly
- **Flexible team sizes** - manage players dynamically

**Share beautiful scorecards, grow your club!** 🚀📱

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

### v2.1 (Latest)
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

*StumpVision v2.1 - Score fast. Share beautiful. Play cricket.* 🏏