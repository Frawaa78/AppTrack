<?php
// public/api/get_latest_work_note.php
session_start();
require_once __DIR__ . '/../../src/db/db.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    $application_id = $_GET['application_id'] ?? null;
    
    if (!$application_id) {
        echo json_encode([
            'success' => false, 
            'error' => 'Application ID is required'
        ]);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Get the most recent work note for this application
    $stmt = $db->prepare('
        SELECT id, note, created_at, created_by
        FROM work_notes 
        WHERE application_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ');
    $stmt->execute([$application_id]);
    $workNote = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($workNote) {
        echo json_encode([
            'success' => true,
            'data' => $workNote
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => null,
            'message' => 'No work notes found'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Get Latest Work Note Error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
