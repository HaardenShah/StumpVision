-- StumpVision Database Schema
-- SQLite Migration v1
-- Creates all tables for database migration from file-based storage

-- Players table
CREATE TABLE IF NOT EXISTS players (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    code TEXT UNIQUE NOT NULL,
    team TEXT,
    player_type TEXT CHECK(player_type IN ('Batsman', 'Bowler', 'All-rounder', 'Wicket-keeper')),
    registered_at INTEGER NOT NULL,
    registered_by TEXT NOT NULL,
    updated_at INTEGER,
    deleted_at INTEGER DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS idx_players_code ON players(code);
CREATE INDEX IF NOT EXISTS idx_players_name ON players(name);
CREATE INDEX IF NOT EXISTS idx_players_team ON players(team);
CREATE INDEX IF NOT EXISTS idx_players_deleted ON players(deleted_at);

-- Matches table
CREATE TABLE IF NOT EXISTS matches (
    id TEXT PRIMARY KEY,
    created_at INTEGER NOT NULL,
    updated_at INTEGER NOT NULL,
    deleted_at INTEGER DEFAULT NULL,

    -- Match metadata
    title TEXT NOT NULL,
    overs_per_side INTEGER NOT NULL,
    wickets_limit INTEGER NOT NULL,

    -- Match data (stored as JSON for flexibility)
    teams TEXT NOT NULL,
    innings TEXT NOT NULL,

    -- Verification
    verified INTEGER DEFAULT 0,
    verified_at INTEGER,
    verified_by TEXT,

    -- Version tracking
    version TEXT DEFAULT '2.3'
);

CREATE INDEX IF NOT EXISTS idx_matches_created_at ON matches(created_at);
CREATE INDEX IF NOT EXISTS idx_matches_verified ON matches(verified);
CREATE INDEX IF NOT EXISTS idx_matches_title ON matches(title);
CREATE INDEX IF NOT EXISTS idx_matches_deleted ON matches(deleted_at);

-- Scheduled matches table
CREATE TABLE IF NOT EXISTS scheduled_matches (
    id TEXT PRIMARY KEY,
    scheduled_date TEXT NOT NULL,
    scheduled_time TEXT NOT NULL,
    match_name TEXT NOT NULL,

    -- Match setup (stored as JSON)
    players TEXT,
    team_a TEXT NOT NULL,
    team_b TEXT NOT NULL,

    -- Match settings
    match_format TEXT NOT NULL,
    overs_per_innings INTEGER,
    wickets_limit INTEGER,

    -- Toss details
    toss_winner TEXT,
    toss_decision TEXT,
    opening_bat1 TEXT,
    opening_bat2 TEXT,
    opening_bowler TEXT,

    -- Link to actual match
    match_id TEXT,

    -- Status
    status TEXT DEFAULT 'scheduled' CHECK(status IN ('scheduled', 'in_progress', 'completed', 'cancelled')),

    -- Audit fields
    created_at INTEGER NOT NULL,
    created_by TEXT NOT NULL,
    updated_at INTEGER,

    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_scheduled_matches_date ON scheduled_matches(scheduled_date);
CREATE INDEX IF NOT EXISTS idx_scheduled_matches_status ON scheduled_matches(status);
CREATE INDEX IF NOT EXISTS idx_scheduled_matches_match_id ON scheduled_matches(match_id);

-- Live sessions table
CREATE TABLE IF NOT EXISTS live_sessions (
    live_id TEXT PRIMARY KEY,
    match_id TEXT NOT NULL,
    scheduled_match_id TEXT,

    created_at INTEGER NOT NULL,
    owner_session TEXT NOT NULL,

    active INTEGER DEFAULT 1,
    current_state TEXT NOT NULL,

    last_updated INTEGER NOT NULL,
    stopped_at INTEGER,
    stopped_by TEXT,

    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (scheduled_match_id) REFERENCES scheduled_matches(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_live_sessions_match_id ON live_sessions(match_id);
CREATE INDEX IF NOT EXISTS idx_live_sessions_active ON live_sessions(active);
CREATE INDEX IF NOT EXISTS idx_live_sessions_created_at ON live_sessions(created_at);

-- Configuration table (optional - for future use)
CREATE TABLE IF NOT EXISTS config (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL,
    updated_at INTEGER NOT NULL,
    updated_by TEXT
);

-- Migration tracking table
CREATE TABLE IF NOT EXISTS migrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    version TEXT NOT NULL UNIQUE,
    applied_at INTEGER NOT NULL,
    description TEXT
);

-- Insert initial migration record
INSERT INTO migrations (version, applied_at, description)
VALUES ('001_initial_schema', strftime('%s', 'now'), 'Initial database schema creation');
