# StumpVision Database Migrations

This directory contains database migration scripts for transitioning StumpVision from file-based JSON storage to SQLite database storage.

## Overview

The migration process consists of two main steps:

1. **Schema Migration** - Creates the database structure (tables, indexes, etc.)
2. **Data Import** - Imports existing data from JSON files into the database

## Files

- `001_initial_schema.sql` - SQL schema definition for all tables
- `migrate.php` - Schema migration runner (creates tables)
- `import_from_files.php` - Data import script (imports JSON data)
- `README.md` - This file

## Prerequisites

Before running migrations, ensure:

1. ✅ PHP 7.4+ is installed
2. ✅ PDO SQLite extension is enabled (`php -m | grep pdo_sqlite`)
3. ✅ `/data/` directory exists and is writable
4. ✅ Existing JSON files are backed up
5. ✅ Database layer is implemented (`/api/lib/Database.php`)

## Migration Steps

### Step 1: Backup Existing Data

**IMPORTANT:** Always backup your data before migration!

```bash
# Create backup directory
mkdir -p backups/pre-migration-$(date +%Y%m%d)

# Backup all JSON files
cp -r data/*.json backups/pre-migration-$(date +%Y%m%d)/
cp -r data/live backups/pre-migration-$(date +%Y%m%d)/

# Verify backup
ls -lh backups/pre-migration-$(date +%Y%m%d)/
```

### Step 2: Run Schema Migration

This creates the database structure (tables, indexes, foreign keys).

```bash
cd /path/to/stumpvision
php migrations/migrate.php
```

**Expected Output:**
```
===========================================
StumpVision Database Schema Migration
===========================================

✓ Database connection established
  Database path: /path/to/data/stumpvision.db

Reading schema file...
Applying database schema...

✓ Schema applied successfully!

Created tables:
  - players
  - matches
  - scheduled_matches
  - live_sessions
  - config
  - migrations

Database statistics:
  - File size: 0.01 MB
  - Table count: 6

===========================================
✓ Migration completed successfully!
===========================================
```

### Step 3: Run Data Import

This imports all existing data from JSON files into the database.

```bash
php migrations/import_from_files.php
```

**Expected Output:**
```
===========================================
StumpVision Data Import from Files
===========================================

✓ Database connection established
✓ Database schema verified

Starting data import...

[1/4] Importing players...
  ✓ Imported 45 players

[2/4] Importing matches...
  ✓ Imported 123 matches

[3/4] Importing scheduled matches...
  ✓ Imported 5 scheduled matches

[4/4] Importing live sessions...
  ✓ Imported 2 live sessions

===========================================
✓ Data Import Complete!
===========================================

Import Summary:
---------------
Players:            45 succeeded,   0 failed,   0 skipped
Matches:           123 succeeded,   0 failed
Scheduled Matches:   5 succeeded,   0 failed
Live Sessions:       2 succeeded,   0 failed

Database Statistics:
--------------------
File size: 2.45 MB
Tables: 6

Row counts:
  players:             45
  matches:             123
  scheduled_matches:   5
  live_sessions:       2
  config:              0
  migrations:          1
```

### Step 4: Verify Data Integrity

After migration, verify that all data was imported correctly:

```bash
# Check database file
ls -lh data/stumpvision.db

# Query database using PHP
php -r "
require 'api/lib/Database.php';
\$db = StumpVision\Database::getInstance();
\$stats = \$db->getStats();
print_r(\$stats);
"
```

Or use SQLite CLI:

```bash
sqlite3 data/stumpvision.db

# Run queries
SELECT COUNT(*) FROM players;
SELECT COUNT(*) FROM matches;
SELECT COUNT(*) FROM matches WHERE verified = 1;
SELECT * FROM migrations;

# Exit
.quit
```

## Troubleshooting

### Migration Already Applied

If you see:
```
⚠ WARNING: Database already has migrations table.
```

This means the schema was previously applied. You can:
- Type `yes` to continue anyway (will skip CREATE IF NOT EXISTS)
- Type `no` to cancel
- Delete the database file and start fresh: `rm data/stumpvision.db`

### Data Import Errors

If you see failed imports:
```
⚠ Failed: 5
```

Check the error details at the bottom of the output. Common issues:

1. **Duplicate ID** - Record already exists (safe to ignore if re-running)
2. **Missing required field** - Data corruption in JSON file
3. **Invalid JSON** - Malformed JSON file

The import uses transactions, so partial failures won't corrupt the database.

### Permission Errors

If you see:
```
✗ Migration FAILED!
  Error: unable to open database file
```

Fix permissions:
```bash
# Ensure web server owns the data directory
sudo chown -R www-data:www-data data/

# Ensure directory is writable
chmod 755 data/
```

### Schema Errors

If schema creation fails, check:
```bash
# Verify Database.php exists
ls -l api/lib/Database.php

# Check PHP SQLite extension
php -m | grep pdo_sqlite

# Check SQL syntax
cat migrations/001_initial_schema.sql
```

## Rolling Back

If you need to roll back to file-based storage:

1. **Delete the database file:**
   ```bash
   rm data/stumpvision.db
   ```

2. **Restore from backup:**
   ```bash
   cp backups/pre-migration-YYYYMMDD/*.json data/
   ```

3. **Revert code changes** (if API updates were deployed)

## Database Location

- **Database file:** `/data/stumpvision.db`
- **Database type:** SQLite 3
- **Journal mode:** WAL (Write-Ahead Logging)
- **Foreign keys:** Enabled
- **Default permissions:** 644 (readable by web server)

## Schema Version Tracking

The `migrations` table tracks applied migrations:

```sql
SELECT * FROM migrations;
```

Output:
```
id | version             | applied_at  | description
---+---------------------+-------------+---------------------------
1  | 001_initial_schema  | 1699123456  | Initial database schema creation
```

## Post-Migration

After successful migration:

1. ✅ Keep JSON files as backup for 30 days
2. ✅ Update API endpoints to use database (Phase 2)
3. ✅ Test all functionality thoroughly
4. ✅ Monitor error logs for issues
5. ✅ Update documentation

## Next Steps

1. **Phase 2:** Update API endpoints to use database repositories
2. **Phase 3:** Update admin panel to use database
3. **Phase 4:** Testing and deployment

See `MIGRATION_STRATEGY.md` for the full migration plan.

## Support

If you encounter issues:

1. Check error logs: `tail -f /var/log/php_errors.log`
2. Review database stats: `php -r "...getStats()..."`
3. Verify backups are intact
4. Check SQLite version: `sqlite3 --version`
5. Review this README for troubleshooting steps

---

**Important:** Always backup your data before running migrations!
