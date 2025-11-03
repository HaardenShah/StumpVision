# StumpVision v2.3 - Deployment Guide

## Full Stack Setup

### 📁 File Structure

```
stumpvision/
├── index.php                    # Main scoring app
├── setup.php                    # Match setup
├── live.php                     # Live score viewer
├── summary.php                  # Match summary
├── scheduled.php                # View scheduled matches
├── manifest.webmanifest         # PWA config
├── service-worker.js            # Offline support
├── robots.txt                   # SEO config
├── .gitignore                   # Git ignore rules
├── admin/                       # Admin panel
│   ├── index.php               # Dashboard
│   ├── login.php               # Authentication
│   ├── matches.php             # Match management
│   ├── players.php             # Player database
│   ├── live-sessions.php       # Live session monitoring
│   ├── settings.php            # System configuration
│   ├── stats.php               # Statistics dashboard
│   ├── schedule-match.php      # Match scheduling
│   ├── auth.php                # Auth utilities
│   ├── config-helper.php       # Config management
│   └── header.php              # Shared navigation
├── api/
│   ├── matches.php             # Match CRUD API
│   ├── live.php                # Live session API
│   ├── players.php             # Player API
│   ├── scheduled-matches.php   # Scheduling API
│   ├── renderCard.php          # Share card API
│   └── lib/                    # Backend libraries
│       ├── Common.php          # Shared utilities
│       ├── Database.php        # SQLite PDO wrapper
│       ├── CardRenderer.php    # Image generation
│       ├── VideoBuilder.php    # Video export
│       ├── Util.php            # Helpers
│       └── repositories/       # Data access layer
│           ├── MatchRepository.php
│           ├── PlayerRepository.php
│           ├── LiveSessionRepository.php
│           └── ScheduledMatchRepository.php
├── migrations/                  # Database migrations
│   ├── migrate.php             # Migration runner
│   ├── import_from_files.php   # Import from JSON
│   ├── check_requirements.php  # System check
│   ├── 001_initial_schema.sql  # Database schema
│   └── README.md               # Migration docs
├── assets/
│   └── icons/
│       ├── icon-192.png        # App icon (CREATE THIS)
│       └── icon-512.png        # App icon (CREATE THIS)
├── data/                       # Data storage
│   ├── stumpvision.db          # SQLite database (auto-generated)
│   ├── live/                   # Live session files
│   └── .gitignore              # Prevents committing DB
└── config/                     # Configuration
    ├── config.json             # Admin settings (auto-generated)
    ├── config.example.json     # Template config
    └── .gitignore              # Prevents committing config
```

### 🚀 Installation Steps

#### 1. Upload Files
Upload all files to your web server (via FTP, cPanel, or SSH)

#### 2. Set Permissions
```bash
# Data directory (for database and live sessions)
sudo chown -R www-data:www-data data/
sudo mkdir -p data/live
sudo chmod 755 data/ data/live/

# Config directory (for admin password - MORE RESTRICTIVE)
sudo chown -R www-data:www-data config/
sudo chmod 750 config/
# config.json will be created with 600 permissions automatically
```

Or via cPanel File Manager:
- Right-click `data` folder → Permissions → 755
- Right-click `data/live` folder → Permissions → 755
- Right-click `config` folder → Permissions → 750

**Security Notes**:
- Never use 777 permissions in production!
- `config/` has 750 (owner + group only, no public access)
- `config.json` has 600 (only web server can read password hash)
- `data/` has 755 (public can list, only owner can write)

#### 3. Configure Admin Access
1. Visit `https://yourdomain.com/admin/`
2. Set up your admin password on first run
3. Login with your password
4. The password is stored as a bcrypt hash in `/config/config.json`

**IMPORTANT**: Change the default password immediately if it's set to `changeme`!

#### 4. Create Icons
See `ICONS_README.md` for instructions. You need:
- `assets/icons/icon-192.png` (192x192)
- `assets/icons/icon-512.png` (512x512)

#### 5. Test Installation
1. Visit `https://yourdomain.com/setup.php`
2. Set up a test match
3. Try scoring a few balls
4. Match will auto-save on first ball
5. Check admin panel to see the saved match
6. Try starting live score sharing from Settings tab

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
- SQLite 3 with PDO support (standard in PHP 7.4+)
- Apache/Nginx web server
- Write permissions on `/data/` and `/config/` directories

**Optional (for share cards):**
- ImageMagick PHP extension (for image generation)
- FFmpeg (for video generation - fallback to PNG if not available)

### 🔄 Database Migration (Upgrading from v2.2 or earlier)

If you're upgrading from an earlier version that used JSON file storage:

1. **Check requirements**: Visit `/migrations/check_requirements.php`
2. **Run migration**: Visit `/migrations/migrate.php` to create database schema
3. **Import data**: Visit `/migrations/import_from_files.php` to import matches/players
4. **Verify**: Check admin panel to ensure data was migrated correctly

See `/migrations/README.md` for detailed instructions.

**Note**: New installations automatically use SQLite - no migration needed!

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

**"Save failed" / Matches not persisting**
- Check `/data/` directory has write permissions (755 recommended)
- Verify web server user owns the `/data/` directory
- Check PHP error logs for SQLite errors
- Never use 777 permissions in production!

**Can't access admin panel**
- Visit `/admin/` for first-time setup
- Check that PHP sessions are enabled
- Verify `/config/config.json` is writable by web server user
- Ensure `/config/` directory has proper permissions (750)
- Default password is `changeme` - change it immediately!

**Live sharing not working**
- Ensure `/data/live/` directory exists and is writable
- Check browser console for API errors
- Verify match has been saved (has match ID)
- Test the live URL in incognito mode

**Database errors**
- Check that SQLite 3 is installed: `php -m | grep pdo_sqlite`
- Verify `/data/stumpvision.db` is writable by web server
- Check for disk space issues
- Review PHP error logs

**PWA not installing**
- Must be served over HTTPS
- Icons must exist in `/assets/icons/`
- Check browser console for errors

**Haptics not working**
- Enable vibration in phone settings
- Must be on HTTPS
- Some browsers don't support vibration API

### 🔐 Security Notes

StumpVision implements comprehensive security measures:
- ✅ CSRF protection on all mutation endpoints
- ✅ Rate limiting (60 requests/minute per IP)
- ✅ Password hashing with bcrypt
- ✅ File locking to prevent data corruption
- ✅ Input sanitization and validation
- ✅ Secure session management

**Protecting the `/data/` Directory:**

The `/data/` directory contains your database and should be protected from direct web access.

**For Apache:**
Create `/data/.htaccess`:
```apache
Deny from all
```

**For Nginx:**
Add to your server config:
```nginx
location /data/ {
    deny all;
    return 403;
}

location /config/ {
    deny all;
    return 403;
}
```

**File Permissions Best Practices:**
- `config/` directory: 750 (rwxr-x---)
- `config/config.json`: 600 (rw-------)
- `data/` directory: 755 (rwxr-xr-x)
- Database files: 644 (rw-r--r--)

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