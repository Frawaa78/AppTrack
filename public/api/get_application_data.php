<?php
// api/get_application_data.php
require_once __DIR__ . '/../../src/db/db.php';

header('Content-Type: application/json');

// Check if application ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Application ID is required'
    ]);
    exit;
}

$application_id = (int)$_GET['id'];

try {
    $db = Database::getInstance()->getConnection();
    
    // Get application data with timestamps
    $stmt = $db->prepare('
        SELECT 
            id,
            short_description,
            application_service,
            phase,
            status,
            created_at,
            updated_at,
            GREATEST(
                COALESCE(updated_at, created_at),
                COALESCE(created_at, "1970-01-01 00:00:00")
            ) as last_modified
        FROM applications 
        WHERE id = :id
    ');
    
    $stmt->execute([':id' => $application_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        echo json_encode([
            'success' => false,
            'error' => 'Application not found'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $application
    ]);
    
} catch (Exception $e) {
    error_log("Get application data error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to retrieve application data: ' . $e->getMessage()
    ]);
}
?>
