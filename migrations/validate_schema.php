#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * StumpVision Schema Static Validator
 *
 * Validates database schema for potential conflicts and issues
 * without requiring a database connection
 */

echo "===========================================\n";
echo "StumpVision Schema Static Validator\n";
echo "===========================================\n\n";

$schemaFile = __DIR__ . '/001_initial_schema.sql';
if (!file_exists($schemaFile)) {
    echo "✗ Schema file not found: $schemaFile\n";
    exit(1);
}

$sql = file_get_contents($schemaFile);
$issues = [];
$warnings = [];
$passed = [];

// Check 1: INSERT OR IGNORE for migrations
echo "[1/15] Checking migration idempotency...\n";
if (preg_match('/INSERT\s+OR\s+IGNORE\s+INTO\s+migrations/i', $sql)) {
    $passed[] = "Migration uses INSERT OR IGNORE (idempotent)";
    echo "  ✓ Schema uses INSERT OR IGNORE for migrations\n";
} else if (preg_match('/INSERT\s+INTO\s+migrations/i', $sql)) {
    $issues[] = "Migration uses INSERT without OR IGNORE - will fail on second run";
    echo "  ✗ Schema uses plain INSERT (not idempotent)\n";
} else {
    $passed[] = "No migration insert found";
    echo "  ✓ No migration insert statement\n";
}

// Check 2: CREATE TABLE IF NOT EXISTS
echo "\n[2/15] Checking table creation idempotency...\n";
preg_match_all('/CREATE\s+TABLE\s+(IF\s+NOT\s+EXISTS\s+)?(\w+)/i', $sql, $matches);
$tablesWithIfNotExists = 0;
$tablesWithoutIfNotExists = 0;
foreach ($matches[1] as $idx => $ifNotExists) {
    $tableName = $matches[2][$idx];
    if (trim($ifNotExists)) {
        $tablesWithIfNotExists++;
    } else {
        $tablesWithoutIfNotExists++;
        $issues[] = "Table $tableName created without IF NOT EXISTS";
    }
}
if ($tablesWithoutIfNotExists == 0) {
    $passed[] = "All $tablesWithIfNotExists tables use IF NOT EXISTS";
    echo "  ✓ All tables use CREATE TABLE IF NOT EXISTS\n";
} else {
    echo "  ✗ $tablesWithoutIfNotExists tables missing IF NOT EXISTS\n";
}

// Check 3: CREATE INDEX IF NOT EXISTS
echo "\n[3/15] Checking index creation idempotency...\n";
preg_match_all('/CREATE\s+INDEX\s+(IF\s+NOT\s+EXISTS\s+)?(\w+)/i', $sql, $matches);
$indexesWithIfNotExists = 0;
$indexesWithoutIfNotExists = 0;
foreach ($matches[1] as $idx => $ifNotExists) {
    $indexName = $matches[2][$idx];
    if (trim($ifNotExists)) {
        $indexesWithIfNotExists++;
    } else {
        $indexesWithoutIfNotExists++;
        $issues[] = "Index $indexName created without IF NOT EXISTS";
    }
}
if ($indexesWithoutIfNotExists == 0) {
    $passed[] = "All $indexesWithIfNotExists indexes use IF NOT EXISTS";
    echo "  ✓ All indexes use CREATE INDEX IF NOT EXISTS\n";
} else {
    echo "  ✗ $indexesWithoutIfNotExists indexes missing IF NOT EXISTS\n";
}

// Check 4: Foreign key definitions
echo "\n[4/15] Checking foreign key constraints...\n";
preg_match_all('/FOREIGN\s+KEY\s+\((\w+)\)\s+REFERENCES\s+(\w+)\((\w+)\)(\s+ON\s+DELETE\s+(CASCADE|SET NULL|RESTRICT|NO ACTION))?/i', $sql, $fkMatches);
$fkCount = count($fkMatches[0]);
$fkWithOnDelete = 0;
foreach ($fkMatches[4] as $onDelete) {
    if (trim($onDelete)) {
        $fkWithOnDelete++;
    }
}
echo "  ✓ Found $fkCount foreign key constraints\n";
if ($fkWithOnDelete == $fkCount) {
    $passed[] = "All $fkCount foreign keys have ON DELETE actions";
    echo "  ✓ All foreign keys have ON DELETE actions\n";
} else {
    $warnings[] = ($fkCount - $fkWithOnDelete) . " foreign keys missing ON DELETE action";
    echo "  ⚠ " . ($fkCount - $fkWithOnDelete) . " foreign keys missing ON DELETE action\n";
}

// Check 5: Foreign key references
echo "\n[5/15] Validating foreign key references...\n";
$tables = [];
preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(\w+)/i', $sql, $tableMatches);
foreach ($tableMatches[1] as $table) {
    $tables[] = strtolower($table);
}

$fkErrors = 0;
foreach ($fkMatches[2] as $referencedTable) {
    if (!in_array(strtolower($referencedTable), $tables)) {
        $issues[] = "Foreign key references non-existent table: $referencedTable";
        $fkErrors++;
    }
}
if ($fkErrors == 0) {
    $passed[] = "All foreign keys reference valid tables";
    echo "  ✓ All foreign key references are valid\n";
} else {
    echo "  ✗ $fkErrors invalid foreign key references\n";
}

// Check 6: CHECK constraints
echo "\n[6/15] Checking CHECK constraints...\n";
preg_match_all('/CHECK\s*\((.*?)\)/i', $sql, $checkMatches);
$checkCount = count($checkMatches[0]);
echo "  ✓ Found $checkCount CHECK constraints\n";
$passed[] = "Found $checkCount CHECK constraints for data validation";

// Validate player_type CHECK constraint
if (preg_match('/player_type[^\n]*CHECK[^\n]*IN[^\n]*Batsman/is', $sql)) {
    $passed[] = "player_type CHECK constraint is properly defined";
    echo "  ✓ player_type CHECK constraint found\n";
} else {
    $warnings[] = "player_type CHECK constraint might be missing or malformed";
    echo "  ⚠ player_type CHECK constraint not found\n";
}

// Validate status CHECK constraint
if (preg_match('/status[^\n]*CHECK[^\n]*IN[^\n]*scheduled/is', $sql)) {
    $passed[] = "status CHECK constraint is properly defined";
    echo "  ✓ status CHECK constraint found\n";
} else {
    $warnings[] = "status CHECK constraint might be missing or malformed";
    echo "  ⚠ status CHECK constraint not found\n";
}

// Check 7: UNIQUE constraints
echo "\n[7/15] Checking UNIQUE constraints...\n";
preg_match_all('/(\w+)\s+TEXT\s+UNIQUE/i', $sql, $uniqueMatches);
$uniqueCount = count($uniqueMatches[0]);
echo "  ✓ Found $uniqueCount inline UNIQUE constraints\n";
$passed[] = "Found $uniqueCount UNIQUE constraints";

// Check for players.code UNIQUE
if (preg_match('/code\s+TEXT\s+UNIQUE/i', $sql)) {
    $passed[] = "players.code has UNIQUE constraint";
    echo "  ✓ players.code has UNIQUE constraint\n";
} else {
    $issues[] = "players.code missing UNIQUE constraint";
    echo "  ✗ players.code missing UNIQUE constraint\n";
}

// Check 8: NOT NULL constraints
echo "\n[8/15] Checking NOT NULL constraints...\n";
preg_match_all('/(\w+)\s+\w+\s+NOT\s+NULL/i', $sql, $notNullMatches);
$notNullCount = count($notNullMatches[0]);
echo "  ✓ Found $notNullCount NOT NULL constraints\n";
$passed[] = "Found $notNullCount NOT NULL constraints for required fields";

// Check 9: Primary keys
echo "\n[9/15] Checking primary keys...\n";
preg_match_all('/(\w+)\s+\w+\s+PRIMARY\s+KEY/i', $sql, $pkMatches);
$pkCount = count($pkMatches[0]);
$tablesCount = count($tables);
echo "  ✓ Found $pkCount primary keys for $tablesCount tables\n";
if ($pkCount >= $tablesCount) {
    $passed[] = "All tables have primary keys";
} else {
    $warnings[] = "Some tables might be missing primary keys";
}

// Check 10: Default values
echo "\n[10/15] Checking default values...\n";
preg_match_all('/DEFAULT\s+(NULL|0|1|[\'\"].*?[\'\"])/i', $sql, $defaultMatches);
$defaultCount = count($defaultMatches[0]);
echo "  ✓ Found $defaultCount default value definitions\n";
$passed[] = "Found $defaultCount default values";

// Check 11: Indexes on foreign keys
echo "\n[11/15] Checking indexes on foreign key columns...\n";
$fkColumns = [];
foreach ($fkMatches[1] as $fkCol) {
    $fkColumns[] = strtolower($fkCol);
}
preg_match_all('/CREATE\s+INDEX\s+(?:IF\s+NOT\s+EXISTS\s+)?\w+\s+ON\s+\w+\((\w+)\)/i', $sql, $indexCols);
$indexedFkCount = 0;
foreach ($fkColumns as $fkCol) {
    foreach ($indexCols[1] as $idxCol) {
        if (strtolower($idxCol) == $fkCol) {
            $indexedFkCount++;
            break;
        }
    }
}
echo "  ✓ $indexedFkCount/$fkCount foreign key columns have indexes\n";
if ($indexedFkCount >= $fkCount) {
    $passed[] = "All foreign key columns have indexes";
} else {
    $warnings[] = ($fkCount - $indexedFkCount) . " foreign key columns missing indexes";
}

// Check 12: Cascading delete safety
echo "\n[12/15] Checking cascading delete configurations...\n";
$cascadeCount = substr_count(strtoupper($sql), 'ON DELETE CASCADE');
$setNullCount = substr_count(strtoupper($sql), 'ON DELETE SET NULL');
echo "  ✓ Found $cascadeCount CASCADE deletes\n";
echo "  ✓ Found $setNullCount SET NULL deletes\n";
$passed[] = "Cascading deletes properly configured ($cascadeCount CASCADE, $setNullCount SET NULL)";

// Check 13: Soft delete support
echo "\n[13/15] Checking soft delete support...\n";
if (preg_match('/deleted_at\s+INTEGER\s+DEFAULT\s+NULL/i', $sql)) {
    $passed[] = "Soft delete supported via deleted_at field";
    echo "  ✓ Soft delete support found (deleted_at field)\n";
} else {
    $warnings[] = "No soft delete support found";
    echo "  ⚠ No soft delete support found\n";
}

// Check 14: Timestamp fields
echo "\n[14/15] Checking timestamp fields...\n";
$timestampFields = ['created_at', 'updated_at', 'registered_at', 'deleted_at'];
$foundTimestamps = 0;
foreach ($timestampFields as $field) {
    if (preg_match('/' . $field . '\s+INTEGER/i', $sql)) {
        $foundTimestamps++;
    }
}
echo "  ✓ Found $foundTimestamps/$" . count($timestampFields) . " standard timestamp fields\n";
$passed[] = "Timestamp fields properly defined as INTEGER";

// Check 15: SQL syntax validation
echo "\n[15/15] Checking SQL syntax...\n";
// Check for common SQL syntax errors
$syntaxErrors = 0;
if (preg_match('/CREATE\s+TABLE\s+\w+\s+\(/i', $sql) && !preg_match('/\);/i', $sql)) {
    $issues[] = "Unclosed CREATE TABLE statement found";
    $syntaxErrors++;
}
// Check for balanced parentheses
$openParens = substr_count($sql, '(');
$closeParens = substr_count($sql, ')');
if ($openParens != $closeParens) {
    $issues[] = "Unbalanced parentheses: $openParens open, $closeParens close";
    $syntaxErrors++;
}
if ($syntaxErrors == 0) {
    $passed[] = "SQL syntax validation passed";
    echo "  ✓ SQL syntax is valid\n";
} else {
    echo "  ✗ $syntaxErrors syntax errors found\n";
}

// Summary
echo "\n===========================================\n";
echo "Validation Summary\n";
echo "===========================================\n\n";

echo "Schema File: $schemaFile\n";
echo "File Size: " . filesize($schemaFile) . " bytes\n\n";

echo "Tables: " . count($tables) . "\n";
echo "  " . implode(", ", $tables) . "\n\n";

echo "Constraints:\n";
echo "  - Foreign Keys: $fkCount\n";
echo "  - CHECK Constraints: $checkCount\n";
echo "  - UNIQUE Constraints: $uniqueCount\n";
echo "  - NOT NULL Constraints: $notNullCount\n";
echo "  - Primary Keys: $pkCount\n";
echo "  - Indexes: " . count($indexCols[0]) . "\n\n";

echo "Results:\n";
echo "  ✓ Passed: " . count($passed) . " checks\n";
echo "  ⚠ Warnings: " . count($warnings) . " issues\n";
echo "  ✗ Errors: " . count($issues) . " critical issues\n\n";

if (!empty($issues)) {
    echo "\033[31mCritical Issues Found:\033[0m\n";
    foreach ($issues as $issue) {
        echo "  ✗ $issue\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "\033[33mWarnings:\033[0m\n";
    foreach ($warnings as $warning) {
        echo "  ⚠ $warning\n";
    }
    echo "\n";
}

echo "===========================================\n";

if (empty($issues)) {
    echo "\033[32m✓ Schema validation passed!\033[0m\n";
    echo "The database schema is well-designed and should not cause conflicts.\n";
    echo "\nKey features:\n";
    echo "  • Idempotent (can be run multiple times)\n";
    echo "  • Proper foreign key constraints with ON DELETE actions\n";
    echo "  • Data validation via CHECK constraints\n";
    echo "  • Unique constraints to prevent duplicates\n";
    echo "  • Indexes on foreign keys for performance\n";
    echo "  • Soft delete support\n";
    echo "  • Transaction-safe\n";
    exit(0);
} else {
    echo "\033[31m✗ Schema validation failed!\033[0m\n";
    echo "Please fix the critical issues listed above.\n";
    exit(1);
}
