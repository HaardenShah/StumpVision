# StumpVision v2 - Deployment Guide

## Full Stack Setup

### 📁 File Structure

```
stumpvision/
├── index.php                    # Main scoring app (NEW v2)
├── setup.php                    # Match setup (NEW v2)
├── manifest.webmanifest         # PWA config (NEW)
├── service-worker.js            # Offline support (NEW)
├── .htaccess                    # Security (NEW)
├── robots.txt                   # SEO (NEW)
├── api/
│   ├── matches.php              # Save/load API (EXISTING)
│   ├── renderCard.php           # Share card API (EXISTING)
│   └── lib/                     # Helper classes (EXISTING)
│       ├── CardRenderer.php
│       ├── VideoBuilder.php
│       └── Util.php
├── assets/
│   └── icons/
│       ├── icon-192.png         # App icon (CREATE THIS)
│       └── icon-512.png         # App icon (CREATE THIS)
└── data/                        # Match saves directory
    └── cards/                   # Generated share cards
```

### 🚀 Installation Steps

#### 1. Upload Files
Upload all files to your web server (via FTP, cPanel, or SSH)

#### 2. Set Permissions
```bash
chmod 755 data
chmod 755 data/cards
chmod 755 api
chmod 644 .htaccess
```

Or via cPanel File Manager:
- Right-click `data` folder → Permissions → 755
- Right-click `data/cards` folder → Permissions → 755

#### 3. Create Icons
See `ICONS_README.md` for instructions. You need:
- `assets/icons/icon-192.png` (192x192)
- `assets/icons/icon-512.png` (512x512)

#### 4. Test Installation
1. Visit `https://yourdomain.com/setup.php`
2. Set up a test match
3. Try scoring a few balls
4. Click "Save Match" in Settings
5. Try "Share Score Card"

### ✅ Feature Checklist

- [ ] Basic scoring works
- [ ] Match saves successfully
- [ ] Share card generates
- [ ] PWA installs on mobile
- [ ] Works offline after first load
- [ ] Haptics work on mobile

### 📱 Installing as PWA

**Android (Chrome/Edge):**
1. Visit your site
2. Tap menu (⋮) → "Add to Home screen"
3. App appears on home screen

**iOS (Safari):**
1. Visit your site
2. Tap Share button
3. "Add to Home Screen"
4. App appears on home screen

**Desktop (Chrome/Edge):**
1. Visit your site
2. Look for install icon in address bar
3. Click "Install StumpVision"

### 🔧 Dependencies

**Required:**
- PHP 7.4+ (8.x recommended)
- Apache/Nginx web server
- Write permissions on `/data/` directory

**Optional (for share cards):**
- ImageMagick PHP extension (for image generation)
- FFmpeg (for video generation - fallback to PNG if not available)

### 🎨 Share Card Setup

The share card feature uses:
1. Your existing `api/renderCard.php`
2. ImageMagick to create PNG cards
3. FFmpeg to create MP4 videos (optional)

**Check if ImageMagick is installed:**
```php
<?php phpinfo(); ?>
```
Look for "imagick" section.

**If not installed:** Cards will fallback to simple PNG generation or skip.

### 🐛 Troubleshooting

**"Save failed"**
- Check `/data/` directory has write permissions (755 or 777)
- Check PHP error logs

**"Share failed"**
- Save the match first
- Check ImageMagick is installed
- Check `/data/cards/` directory exists and is writable

**PWA not installing**
- Must be served over HTTPS
- Icons must exist in `/assets/icons/`
- Check browser console for errors

**Haptics not working**
- Enable vibration in phone settings
- Must be on HTTPS
- Some browsers don't support vibration API

### 🔐 Security Notes

The `.htaccess` file protects:
- `/data/` directory from direct access
- Sensitive file types
- Directory browsing

Make sure your server supports `.htaccess` (Apache) or configure nginx equivalent.

### 📈 Next Steps

Once deployed:
1. Test thoroughly with real matches
2. Share with your cricket club
3. Gather feedback
4. Monitor `/data/` directory size (clean old matches periodically)

### 🆘 Support

If you need help:
1. Check PHP error logs: `tail -f /var/log/php_errors.log`
2. Check browser console for JavaScript errors
3. Verify file permissions
4. Test API endpoints directly: `/api/matches.php?action=list`

Good luck with your cricket club! 🏏