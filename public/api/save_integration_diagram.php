<?php
// public/api/save_integration_diagram.php
session_start();
require_once __DIR__ . '/../../src/db/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if (!in_array($_SESSION['user_role'] ?? 'viewer', ['admin', 'editor'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Only admins and editors can save integration diagrams.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['application_id'])) {
    echo json_encode(['success' => false, 'error' => 'Application ID required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare('UPDATE applications SET integration_diagram = ?, integration_notes = ? WHERE id = ?');
    $result = $stmt->execute([
        $input['diagram_code'] ?? '',
        $input['notes'] ?? '',
        $input['application_id']
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Integration diagram and notes saved successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save data']);
    }
} catch (Exception $e) {
    error_log("Error saving integration diagram: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
