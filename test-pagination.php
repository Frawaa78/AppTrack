<?php
// Test pagination functionality
session_start();
require_once __DIR__ . '/src/managers/ActivityManager.php';

// Simulate user session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

$activityManager = new ActivityManager();
$applicationId = 429;

echo "Testing pagination functionality...\n\n";

// Test 1: Get total count
try {
    $totalCount = $activityManager->getActivityCount($applicationId);
    echo "Total activities: $totalCount\n";
} catch (Exception $e) {
    echo "Error getting count: " . $e->getMessage() . "\n";
}

// Test 2: Get first 5 activities
try {
    $activities = $activityManager->getActivityFeed($applicationId, [], 5, 0);
    echo "First 5 activities: " . count($activities) . " found\n";
    
    if (!empty($activities)) {
        echo "First activity: " . $activities[0]['content'] . "\n";
    }
} catch (Exception $e) {
    echo "Error getting activities: " . $e->getMessage() . "\n";
}

// Test 3: Get next 5 activities
try {
    $activities = $activityManager->getActivityFeed($applicationId, [], 5, 5);
    echo "Next 5 activities: " . count($activities) . " found\n";
} catch (Exception $e) {
    echo "Error getting next activities: " . $e->getMessage() . "\n";
}

echo "\nTesting complete.\n";
?>
