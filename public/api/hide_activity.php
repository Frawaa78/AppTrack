<?php
// public/api/hide_activity.php
session_start();
require_once __DIR__ . '/../../src/managers/ActivityManager.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$activity_type = $input['activity_type'] ?? null;
$activity_id = $input['activity_id'] ?? null;

if (!$activity_type || !$activity_id) {
    echo json_encode(['success' => false, 'error' => 'Activity type and ID are required']);
    exit;
}

try {
    $activityManager = new ActivityManager();
    $success = $activityManager->hideActivity($activity_type, $activity_id, $_SESSION['role']);
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to hide activity']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error hiding activity: ' . $e->getMessage()
    ]);
}
?>
