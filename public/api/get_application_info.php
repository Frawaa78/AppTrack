<?php
// public/api/get_application_info.php
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
    $application_id = $_GET['id'] ?? null;
    
    if (!$application_id) {
        echo json_encode([
            'success' => false, 
            'error' => 'Application ID is required'
        ]);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Get application basic info with timestamps
    $stmt = $db->prepare('
        SELECT id, short_description, created_at, 
               COALESCE(updated_at, created_at) as updated_at
        FROM applications 
        WHERE id = ?
    ');
    $stmt->execute([$application_id]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$app) {
        echo json_encode(['success' => false, 'error' => 'Application not found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $app
    ]);
    
} catch (Exception $e) {
    error_log('Get Application Info Error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
