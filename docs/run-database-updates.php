<?php
// docs/run-database-updates.php
// KJØR DENNE FILEN KUN ÉN GANG for å oppdatere databasen

require_once __DIR__ . '/../src/db/db.php';

echo "<h2>Database Updates for AppTrack</h2>\n";
echo "<pre>\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Liste over SQL-kommandoer å kjøre
    $updates = [
        // work_notes tabell oppdateringer
        "ALTER TABLE work_notes 
         ADD COLUMN priority ENUM('low', 'medium', 'high') DEFAULT 'medium'",
        
        "ALTER TABLE work_notes 
         ADD COLUMN is_visible BOOLEAN DEFAULT 1",
        
        "ALTER TABLE work_notes 
         ADD COLUMN attachment_data LONGBLOB NULL",
        
        "ALTER TABLE work_notes 
         ADD COLUMN attachment_filename VARCHAR(255) NULL",
        
        "ALTER TABLE work_notes 
         ADD COLUMN attachment_size INT NULL",
        
        "ALTER TABLE work_notes 
         ADD COLUMN attachment_mime_type VARCHAR(100) NULL",
        
        // audit_log tabell oppdateringer
        "ALTER TABLE audit_log
         ADD COLUMN change_summary VARCHAR(500)",
        
        "ALTER TABLE audit_log
         ADD COLUMN is_visible BOOLEAN DEFAULT 1",
        
        // Indekser
        "CREATE INDEX idx_work_notes_app_created ON work_notes(application_id, created_at DESC)",
        "CREATE INDEX idx_audit_log_record_created ON audit_log(table_name, record_id, created_at DESC)",
        "CREATE INDEX idx_work_notes_visible ON work_notes(is_visible)",
        "CREATE INDEX idx_audit_log_visible ON audit_log(is_visible)"
    ];
    
    foreach ($updates as $index => $sql) {
        try {
            echo "Running update " . ($index + 1) . "...\n";
            echo "SQL: " . substr($sql, 0, 50) . "...\n";
            
            $db->exec($sql);
            echo "✅ SUCCESS\n\n";
            
        } catch (PDOException $e) {
            // Ignorer feil hvis kolonne/indeks allerede eksisterer
            if (strpos($e->getMessage(), 'Duplicate column name') !== false ||
                strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "⚠️  SKIPPED (already exists)\n\n";
            } else {
                echo "❌ ERROR: " . $e->getMessage() . "\n\n";
            }
        }
    }
    
    echo "Database updates completed!\n";
    echo "\nYou can now delete this file for security.\n";
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
pre { background: #f5f5f5; padding: 15px; border-radius: 5px; }
</style>
