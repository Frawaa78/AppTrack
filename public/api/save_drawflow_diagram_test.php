<?php
require_once '../../src/db/db.php';
require_once '../../src/config/config.php';

// No authentication required for testing
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $application_id = isset($input['application_id']) ? intval($input['application_id']) : null;
    $diagram_data = isset($input['diagram_data']) ? $input['diagram_data'] : null;
    $notes = isset($input['notes']) ? $input['notes'] : '';
    
    if (!$application_id || !$diagram_data) {
        throw new Exception('Missing required fields: application_id, diagram_data');
    }
    
    // Connect to database
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Check if application exists, if not create a basic record
    $stmt = $pdo->prepare("SELECT id FROM applications WHERE id = ?");
    $stmt->execute([$application_id]);
    
    if (!$stmt->fetch()) {
        // Create basic application record for testing
        $stmt = $pdo->prepare("INSERT INTO applications (id, name, description, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$application_id, "Test Application $application_id", "Auto-created for testing", 'active']);
    }
    
    // Update the drawflow diagram
    $stmt = $pdo->prepare("UPDATE applications SET drawflow_diagram = ?, drawflow_notes = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([json_encode($diagram_data), $notes, $application_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Diagram saved successfully',
            'application_id' => $application_id
        ]);
    } else {
        throw new Exception('Failed to save diagram');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
