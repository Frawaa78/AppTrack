<?php
// public/api/save_integration_diagram.php
session_start();
require_once __DIR__ . '/../../src/db/db.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the incoming request for debugging
error_log("SAVE DIAGRAM: Incoming request");
error_log("SAVE DIAGRAM: Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log("SAVE DIAGRAM: Session user_role: " . ($_SESSION['user_role'] ?? 'NOT SET'));

if (!isset($_SESSION['user_id'])) {
    error_log("SAVE DIAGRAM: User not authenticated");
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if (!in_array($_SESSION['user_role'] ?? 'viewer', ['admin', 'editor'])) {
    error_log("SAVE DIAGRAM: User unauthorized. Role: " . ($_SESSION['user_role'] ?? 'viewer'));
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Only admins and editors can save integration diagrams.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
error_log("SAVE DIAGRAM: Raw input: " . file_get_contents('php://input'));
error_log("SAVE DIAGRAM: Parsed input: " . print_r($input, true));

if (!isset($input['application_id'])) {
    error_log("SAVE DIAGRAM: Application ID missing");
    echo json_encode(['success' => false, 'error' => 'Application ID required']);
    exit;
}

error_log("SAVE DIAGRAM: Application ID: " . $input['application_id']);
error_log("SAVE DIAGRAM: Diagram code length: " . strlen($input['diagram_code'] ?? ''));

try {
    $db = Database::getInstance()->getConnection();
    error_log("SAVE DIAGRAM: Database connection successful");
    
    $stmt = $db->prepare('UPDATE applications SET integration_diagram = ?, integration_notes = ? WHERE id = ?');
    $result = $stmt->execute([
        $input['diagram_code'] ?? '',
        $input['notes'] ?? '',
        $input['application_id']
    ]);
    
    error_log("SAVE DIAGRAM: Query executed. Result: " . ($result ? 'SUCCESS' : 'FAILED'));
    error_log("SAVE DIAGRAM: Affected rows: " . $stmt->rowCount());
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Integration diagram and notes saved successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save data']);
    }
} catch (Exception $e) {
    error_log("SAVE DIAGRAM: Exception caught: " . $e->getMessage());
    error_log("SAVE DIAGRAM: Exception trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
