# 🏏 StumpVision v2 — Cricket Scorer

**StumpVision** is a lightweight, mobile-first web app for scoring cricket matches. Built with **PHP + vanilla JavaScript**, it works completely offline, installs as a PWA, and generates beautiful shareable scorecards for social media.

Perfect for pickup cricket, club matches, and growing your cricket community! 🌟

---

## ⚡ Features

### Core Scoring
✅ **Simple scoring pad** - Record runs, boundaries, wickets, extras with one tap  
✅ **Smart cricket logic** - No-balls trigger free hits, auto-advance overs after 6 legal balls  
✅ **Extras tracking** - NB, WD, Byes, Leg Byes tracked separately  
✅ **Comprehensive stats** - Strike rates, economy rates, balls faced, overs bowled  
✅ **Ball-by-ball tracking** - Complete delivery history with undo support  
✅ **Target tracker** - Real-time chase calculations in 2nd innings  

### Match Setup
✅ **Toss configuration** - Select who won toss and batting/bowling choice  
✅ **Opening players** - Choose opening batsmen and bowler  
✅ **Team rosters** - Add players dynamically with instant validation  
✅ **Match settings** - Overs per innings, wickets limit, match format  

### Mobile Experience
✅ **Sunlight-optimized UI** - High contrast design for outdoor visibility  
✅ **Auto dark mode** - Respects system preference  
✅ **Haptic feedback** - Vibration on scoring actions  
✅ **Touch-optimized** - Large buttons (48px+ tap targets)  
✅ **PWA installable** - Add to home screen, works like native app  
✅ **Offline-first** - Score matches without internet  

### Social Sharing
✅ **Save to server** - Persistent match storage with unique IDs  
✅ **Share scorecards** - Generate beautiful PNG/MP4 graphics  
✅ **Social media ready** - One-tap share to Instagram, WhatsApp, Twitter  
✅ **Live viewer links** - Share read-only live match links (when backend enabled)  

---

## 📱 Screenshots

*Coming soon - add screenshots of your app in action!*

---

## 🚀 Quick Start

### Installation

1. **Upload files** to your web server:
```
stumpvision/
├── index.php              # Main scoring app
├── setup.php              # Match setup
├── manifest.webmanifest   # PWA config
├── service-worker.js      # Offline support
├── .htaccess             # Security
├── robots.txt            # SEO
├── api/
│   ├── matches.php
│   ├── renderCard.php
│   └── lib/
└── assets/icons/
    ├── icon-192.png      # Create this!
    └── icon-512.png      # Create this!
```

2. **Set permissions**:
```bash
chmod 755 data
chmod 755 data/cards
```

3. **Create app icons** (see ICONS_README.md):
   - 192x192px PNG
   - 512x512px PNG

4. **Visit your site**:
```
https://yourdomain.com/setup.php
```

### Requirements
- **PHP 7.4+** (8.x recommended)
- **Apache/Nginx** with `.htaccess` support (Apache)
- **Write permissions** on `/data/` directory
- **ImageMagick** (optional - for share cards)
- **FFmpeg** (optional - for video share cards)

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
- **Swap Strike** if batsmen cross
- **Undo** if you make a mistake
- **View Stats** tab for live statistics
- Overs auto-complete after 6 legal balls
- Select new bowler when prompted

### 3. Save & Share
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
| **Wicket**     | Prompt for new batter; ends innings if all out             |
| **No Ball**    | +1 extra + bat runs; next ball is FREE HIT                  |
| **Wide**       | +1 (or more) to extras; ball doesn't count                  |
| **Bye/Leg Bye**| Runs to extras; counts as legal ball                        |
| **Free Hit**   | No wicket possible (except run out)                         |
| **Undo**       | Reverts last ball completely                                |

---

## 🏗️ Technical Details

### Stack
- **Frontend**: Vanilla JavaScript (ES6+), HTML5, CSS3
- **Backend**: PHP 8.x with flat-file JSON storage
- **Storage**: localStorage (client) + `/data/*.json` (server)
- **Offline**: Service Worker + Cache API
- **Share Cards**: ImageMagick + FFmpeg (optional)

### File Structure
```
Core App (v2):
├── index.php          - Main app (all-in-one: HTML + CSS + JS)
├── setup.php          - Match setup (all-in-one: HTML + CSS + JS)

Backend API:
├── api/matches.php    - CRUD for match data
├── api/renderCard.php - Generate share graphics
└── api/lib/           - Image/video rendering classes

PWA:
├── manifest.webmanifest - App metadata
└── service-worker.js    - Offline caching

Data:
├── data/*.json        - Saved matches
└── data/cards/        - Generated share cards
```

### Why Inline CSS/JS?
- **Simpler deployment** - Just 2 main files
- **Faster first load** - No extra HTTP requests
- **Easier maintenance** - Everything in one place
- **Still performant** - Cached by service worker

---

## 🔧 Configuration

### PWA Settings
Edit `manifest.webmanifest`:
```json
{
  "name": "Your Club Name - Cricket Scorer",
  "theme_color": "#YOUR_COLOR",
  "start_url": "/your-path/index.php"
}
```

### Service Worker
Update cache version in `service-worker.js` when you make changes:
```javascript
const VERSION = 'stumpvision-v2.1'; // Increment this
```

### Security
The `.htaccess` file protects:
- `/data/` directory from public access
- Sensitive file extensions
- Directory browsing disabled

---

## 📱 Installing as App

### Android
1. Open in Chrome/Edge
2. Menu → "Add to Home screen"
3. App appears on home screen

### iOS
1. Open in Safari
2. Share button → "Add to Home Screen"
3. App appears on home screen

### Desktop
1. Open in Chrome/Edge
2. Install icon in address bar
3. Installs like desktop app

---

## 🎨 Customization

### Colors
Edit CSS variables in `index.php` and `setup.php`:
```css
:root {
  --accent: #0ea5e9;      /* Blue */
  --danger: #dc2626;      /* Red */
  --success: #16a34a;     /* Green */
}
```

### Share Cards
Customize in `api/lib/CardRenderer.php`:
- Card layout
- Colors and fonts
- Branding elements

---

## 🐛 Troubleshooting

### "Save failed"
- Check `/data/` has write permissions (755 or 777)
- Verify PHP error logs
- Ensure `api/matches.php` is accessible

### "Share failed"
- Save match first
- Check ImageMagick is installed: `php -m | grep imagick`
- Verify `/data/cards/` exists and is writable

### PWA not installing
- **Must use HTTPS** (required for PWA)
- Icons must exist in `assets/icons/`
- Check browser console for errors

### Haptics not working
- Enable vibration in phone settings
- Must be on HTTPS
- Some browsers don't support vibration API

### Stats page empty
- Stats only show for players who have batted/bowled
- Check that match has started and balls have been recorded

---

## 🔐 Security

- ✅ `.htaccess` protects sensitive directories
- ✅ No database = no SQL injection
- ✅ Input sanitization on save
- ✅ Read-only live viewer mode available
- ✅ No authentication by default (add if needed)

---

## 🗺️ Roadmap

**v2.1 (Next)**
- [ ] Match history/archive view
- [ ] Export to CSV
- [ ] Custom scoring rules (super over, etc.)
- [ ] Multi-language support

**v2.2 (Future)**
- [ ] Live streaming scores (WebSocket)
- [ ] Team statistics dashboard
- [ ] Tournament bracket mode
- [ ] Player profiles

---

## 🤝 Contributing

This is a solo project for now, but feel free to:
- Report bugs via GitHub issues
- Suggest features
- Fork and customize for your club

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

**Tech Philosophy:**
- No frameworks, no bloat
- Vanilla JavaScript = fast & reliable
- Progressive enhancement
- Mobile-first design

---

## 🆘 Support

Need help? Check:
1. `DEPLOYMENT.md` - Detailed setup guide
2. `ICONS_README.md` - Icon creation guide
3. PHP error logs: `/var/log/php_errors.log`
4. Browser console for JavaScript errors

---

## 🏏 Perfect For

- Cricket clubs building community
- Pickup matches in parks
- School/college tournaments  
- Social media content creation
- Growing cricket awareness

**Share your scorecards, grow your club!** 🚀

---

*StumpVision v2 - Score fast. Share easy. Play cricket.* 🏏