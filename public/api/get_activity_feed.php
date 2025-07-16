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
    
    // Pagination parameters
    $limit = $input['limit'] ?? 5;  // Default to 5 activities per page
    $offset = $input['offset'] ?? 0; // Default to start from beginning
    
    // Kun admin kan se skjulte aktiviteter
    if (isset($filters['show_hidden']) && $filters['show_hidden'] && $_SESSION['role'] !== 'admin') {
        $filters['show_hidden'] = false;
    }
    
    $activities = $activityManager->getActivityFeed($input['application_id'], $filters, $limit, $offset);
    
    // Get total count for pagination info
    $totalCount = $activityManager->getActivityCount($input['application_id'], $filters);
    $hasMore = ($offset + $limit) < $totalCount;
    
    echo json_encode([
        'success' => true,
        'activities' => $activities,
        'pagination' => [
            'total' => $totalCount,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => $hasMore
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error loading activity feed: ' . $e->getMessage()
    ]);
}
?>
