# Installation Edge Cases and Fixes

This document outlines edge cases discovered in the installation process and the fixes implemented.

## Issues Found and Fixed

### 1. Incomplete Installation Detection (CRITICAL)

**Issue:**
The original `InstallCheck::isInstalled()` only verified that the database file exists, not that it contains the proper schema. This caused problems when:
- Database file was auto-created by SQLite but migration failed
- Installation was interrupted mid-process
- Someone manually created an empty database file

**Symptoms:**
```
Fatal error: SQLSTATE[HY000]: General error: 1 no such table: matches
```

**Fix Applied:**
Enhanced `InstallCheck::isInstalled()` (api/lib/InstallCheck.php:15-73) to verify:
1. Both database file AND config.json exist
2. Config.json is valid JSON with required fields (admin_username, admin_password_hash, installed flag)
3. All critical tables exist: migrations, players, matches, scheduled_matches, live_sessions
4. At least one migration record is present

### 2. API Endpoints Missing Installation Check (CRITICAL)

**Issue:**
All API endpoints (players.php, matches.php, live.php, scheduled-matches.php) were missing installation checks. They directly instantiated repositories which triggered `Database::getInstance()`, causing SQLite to auto-create an empty database file before installation was complete.

**Attack Vector:**
1. Fresh installation - no database exists
2. User (or bot) calls `/api/players.php` before running installer
3. SQLite auto-creates empty database file
4. Installation process thinks system is "already installed" (file exists)
5. Admin panel shows "no such table" errors

**Fix Applied:**
Added installation check to all API endpoints:
- api/players.php:19-29
- api/matches.php:24-34
- api/live.php:21-31
- api/scheduled-matches.php:19-29

Each endpoint now returns HTTP 503 with JSON error if not installed:
```json
{
  "ok": false,
  "error": "not_installed",
  "message": "StumpVision is not installed. Please complete the installation first."
}
```

### 3. Partial Migration Detection

**Issue:**
If migration script ran but only some tables were created (due to error or interruption), the system wouldn't detect the incomplete state.

**Fix Applied:**
`InstallCheck::isInstalled()` now validates ALL critical tables exist, not just checking for migrations table. This catches:
- Partial migrations
- Manually deleted tables
- Corrupted database state

### 4. Config Validation

**Issue:**
Config.json could be:
- Empty or corrupted JSON
- Missing required fields (admin credentials)
- Exist but with invalid structure

**Fix Applied:**
Added config validation in `InstallCheck::isInstalled()` (lines 25-43):
- Verifies file can be read
- Validates JSON structure
- Checks for required fields: admin_username, admin_password_hash, installed
- Returns false if any check fails, triggering re-installation

## Edge Cases Now Handled

### Database File Auto-Creation
✅ **Handled:** Even if SQLite auto-creates an empty file, the installation check verifies table existence

### Race Conditions During Installation
✅ **Handled:** API endpoints check installation status before accessing database

### Interrupted Installation
✅ **Handled:** `install.php` cleanup logic (lines 88-93) removes both database and config on error

### Corrupted Installation
✅ **Handled:** Comprehensive validation ensures all components are present and valid

### Manual File Manipulation
✅ **Handled:** System validates actual database contents, not just file existence

### Missing Critical Tables
✅ **Handled:** All 5 critical tables must exist for installation to be considered complete

### Invalid Config
✅ **Handled:** JSON validation and required field checks prevent corrupted config from appearing "installed"

## Testing Recommendations

To test these fixes, try:

1. **Incomplete Installation Test:**
   - Delete migration records: `DELETE FROM migrations`
   - Try to access admin panel (should redirect to installer)

2. **Partial Migration Test:**
   - Drop one table: `DROP TABLE matches`
   - Try to access admin panel (should redirect to installer)

3. **API Protection Test:**
   - Delete database file
   - Call `/api/players.php` directly
   - Should receive 503 error, NOT create empty database

4. **Config Corruption Test:**
   - Edit config.json to invalid JSON
   - Try to access admin panel (should redirect to installer)

5. **Missing Config Fields Test:**
   - Remove `admin_password_hash` from config.json
   - Try to access admin panel (should redirect to installer)

## Files Modified

- `api/lib/InstallCheck.php` - Enhanced validation logic
- `api/players.php` - Added installation check
- `api/matches.php` - Added installation check
- `api/live.php` - Added installation check
- `api/scheduled-matches.php` - Added installation check

## Related Files

- `api/install.php` - Installation script with cleanup logic
- `migrations/001_initial_schema.sql` - Database schema definition
- `migrations/web_migrate.php` - Web-based migration tool
- `admin/config-helper.php` - Config management with fallbacks

## Future Considerations

1. **Database Integrity Check Tool:**
   - Could add admin panel tool to verify database integrity
   - Check for orphaned records, broken foreign keys, etc.

2. **Installation Repair Mode:**
   - Could add `/install.php?repair=1` to fix corrupted installations
   - Would verify each component and repair what's broken

3. **Migration Rollback:**
   - Currently no rollback mechanism if migration fails
   - Could add migration versioning and rollback support

4. **Health Check Endpoint:**
   - Add `/api/health.php` to verify system status
   - Could be used by monitoring tools

## Version History

- **v2.4** (2024-11) - Comprehensive installation validation and API protection
- **v2.3** - Initial database migration support
