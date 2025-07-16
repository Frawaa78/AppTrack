<?php
// Fix future timestamps in database
require_once __DIR__ . '/src/db/db.php';

echo "<h3>Fixing Future Timestamps</h3>\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Find work notes with future timestamps
    $stmt = $db->query("
        SELECT id, created_at, updated_at, note 
        FROM work_notes 
        WHERE created_at > NOW() 
        ORDER BY created_at DESC
    ");
    $futureNotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<strong>Work notes with future timestamps:</strong>\n";
    foreach ($futureNotes as $note) {
        echo "ID: {$note['id']} - Created: {$note['created_at']} - Note: " . substr($note['note'], 0, 50) . "...\n";
    }
    
    // Find audit logs with future timestamps
    $stmt = $db->query("
        SELECT id, changed_at, field_name, new_value 
        FROM audit_log 
        WHERE changed_at > NOW() 
        ORDER BY changed_at DESC
    ");
    $futureLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n<strong>Audit logs with future timestamps:</strong>\n";
    foreach ($futureLogs as $log) {
        echo "ID: {$log['id']} - Changed: {$log['changed_at']} - Field: {$log['field_name']}\n";
    }
    
    // Option to fix them (set to current time)
    if (!empty($futureNotes) || !empty($futureLogs)) {
        echo "\n<strong>To fix these, you can run:</strong>\n";
        echo "UPDATE work_notes SET created_at = NOW(), updated_at = NOW() WHERE created_at > NOW();\n";
        echo "UPDATE audit_log SET changed_at = NOW() WHERE changed_at > NOW();\n";
        
        // Actually fix them
        if (isset($_GET['fix']) && $_GET['fix'] === 'true') {
            echo "\n<strong>Fixing timestamps...</strong>\n";
            
            $fixedNotes = $db->exec("UPDATE work_notes SET created_at = NOW(), updated_at = NOW() WHERE created_at > NOW()");
            $fixedLogs = $db->exec("UPDATE audit_log SET changed_at = NOW() WHERE changed_at > NOW()");
            
            echo "Fixed {$fixedNotes} work notes\n";
            echo "Fixed {$fixedLogs} audit log entries\n";
            echo "Done! Future timestamps have been corrected.\n";
        } else {
            echo "\nAdd ?fix=true to the URL to actually fix these timestamps.\n";
        }
    } else {
        echo "\nNo future timestamps found - everything looks good!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
