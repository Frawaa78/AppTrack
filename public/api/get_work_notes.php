<?php
// api/get_work_notes.php
require_once __DIR__ . '/../../src/db/db.php';

header('Content-Type: application/json');

// Check if application ID is provided
if (!isset($_GET['application_id']) || empty($_GET['application_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Application ID is required'
    ]);
    exit;
}

$application_id = (int)$_GET['application_id'];
$since = $_GET['since'] ?? null;

try {
    $db = Database::getInstance()->getConnection();
    
    // Build query based on whether 'since' parameter is provided
    if ($since) {
        // Get work notes since a specific date
        $stmt = $db->prepare('
            SELECT 
                id,
                application_id,
                content,
                created_at,
                updated_at
            FROM work_notes 
            WHERE application_id = :application_id 
                AND created_at > :since
            ORDER BY created_at DESC
        ');
        
        $stmt->execute([
            ':application_id' => $application_id,
            ':since' => $since
        ]);
    } else {
        // Get all work notes for the application
        $stmt = $db->prepare('
            SELECT 
                id,
                application_id,
                content,
                created_at,
                updated_at
            FROM work_notes 
            WHERE application_id = :application_id
            ORDER BY created_at DESC
        ');
        
        $stmt->execute([':application_id' => $application_id]);
    }
    
    $work_notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $work_notes,
        'count' => count($work_notes),
        'since' => $since
    ]);
    
} catch (Exception $e) {
    error_log("Get work notes error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to retrieve work notes: ' . $e->getMessage()
    ]);
}
?>
