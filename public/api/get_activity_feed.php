<?php
// public/api/get_activity_feed.php
session_start();
require_once __DIR__ . '/../../src/managers/ActivityManager.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['application_id'])) {
    echo json_encode(['success' => false, 'error' => 'Application ID required']);
    exit;
}

try {
    $activityManager = new ActivityManager();
    $filters = $input['filters'] ?? [];
    
    // Kun admin kan se skjulte aktiviteter
    if (isset($filters['show_hidden']) && $filters['show_hidden'] && $_SESSION['role'] !== 'admin') {
        $filters['show_hidden'] = false;
    }
    
    $activities = $activityManager->getActivityFeed($input['application_id'], $filters);
    
    echo json_encode([
        'success' => true,
        'activities' => $activities
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error loading activity feed: ' . $e->getMessage()
    ]);
}
?>
