<?php
require_once '../../src/db/db.php';
require_once '../../src/config/config.php';

// No authentication required for testing
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $application_id = isset($_GET['application_id']) ? intval($_GET['application_id']) : null;
    
    if (!$application_id) {
        throw new Exception('Missing application_id parameter');
    }
    
    // Connect to database
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Get the drawflow diagram
    $stmt = $pdo->prepare("SELECT drawflow_diagram, drawflow_notes FROM applications WHERE id = ?");
    $stmt->execute([$application_id]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
    } else {
        // No data found - return empty success
        echo json_encode([
            'success' => true,
            'data' => null,
            'message' => 'No diagram found for this application'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
