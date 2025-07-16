<?php
// test-session-and-api.php
session_start();

echo "<h1>Session and API Test</h1>";

echo "<h2>Session Information:</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "User Role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
echo "User Email: " . ($_SESSION['email'] ?? 'NOT SET') . "\n";
echo "Full Session: ";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Testing ActivityManager:</h2>";
try {
    require_once __DIR__ . '/src/managers/ActivityManager.php';
    echo "✅ ActivityManager loaded successfully<br>";
    
    $activityManager = new ActivityManager();
    echo "✅ ActivityManager instance created<br>";
    
    if (isset($_SESSION['user_id'])) {
        $activities = $activityManager->getActivityFeed(429, []);
        echo "✅ getActivityFeed called successfully<br>";
        echo "📊 Found " . count($activities) . " activities<br>";
    } else {
        echo "❌ Cannot test getActivityFeed - user not logged in<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "📍 File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
}

echo "<h2>Database Connection Test:</h2>";
try {
    require_once __DIR__ . '/src/db/db.php';
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connection successful<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}
?>
