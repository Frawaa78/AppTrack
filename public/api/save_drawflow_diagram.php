<?php
// Use absolute paths for one.com server
$rootPath = dirname(dirname(dirname(__FILE__)));
require_once $rootPath . '/src/db/db.php';
require_once $rootPath . '/src/config/config.php';

// No authentication required for testing
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['application_id']) || !isset($input['diagram_data'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$application_id = intval($input['application_id']);
$diagram_data = json_encode($input['diagram_data']);
$notes = isset($input['notes']) ? $input['notes'] : '';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("UPDATE applications SET drawflow_diagram = ?, drawflow_notes = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$diagram_data, $notes, $application_id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Diagram saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save diagram']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
