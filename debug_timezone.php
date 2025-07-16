<?php
// Debug timezone issues
require_once __DIR__ . '/src/db/db.php';

echo "<h3>Timezone Debug Information</h3>\n\n";

// PHP timezone
echo "<strong>PHP Timezone:</strong> " . date_default_timezone_get() . "\n";
echo "<strong>PHP Current Time:</strong> " . date('Y-m-d H:i:s') . "\n\n";

// MySQL timezone
try {
    $db = Database::getInstance()->getConnection();
    
    // Check MySQL timezone
    $stmt = $db->query("SELECT @@global.time_zone as global_tz, @@session.time_zone as session_tz, NOW() as mysql_now, UTC_TIMESTAMP() as utc_now");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<strong>MySQL Global Timezone:</strong> " . $result['global_tz'] . "\n";
    echo "<strong>MySQL Session Timezone:</strong> " . $result['session_tz'] . "\n";
    echo "<strong>MySQL NOW():</strong> " . $result['mysql_now'] . "\n";
    echo "<strong>MySQL UTC_TIMESTAMP():</strong> " . $result['utc_now'] . "\n\n";
    
    // Check latest work note
    $stmt = $db->query("SELECT id, created_at FROM work_notes ORDER BY created_at DESC LIMIT 1");
    $latest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($latest) {
        echo "<strong>Latest work note timestamp:</strong> " . $latest['created_at'] . "\n";
        echo "<strong>Latest work note ID:</strong> " . $latest['id'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}

// JavaScript client time (will be filled by JS)
echo "\n<script>
document.addEventListener('DOMContentLoaded', function() {
    const now = new Date();
    const clientTime = now.toISOString();
    const localTime = now.toLocaleString();
    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    
    document.body.innerHTML += '<br><strong>Client Timezone:</strong> ' + timezone + '<br>';
    document.body.innerHTML += '<strong>Client Local Time:</strong> ' + localTime + '<br>';
    document.body.innerHTML += '<strong>Client UTC Time:</strong> ' + clientTime + '<br>';
});
</script>";
?>
