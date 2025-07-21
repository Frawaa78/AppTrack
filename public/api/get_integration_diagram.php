<?php
// public/api/get_integration_diagram.php
session_start();
require_once __DIR__ . '/../../src/db/db.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the incoming request for debugging
error_log("GET DIAGRAM: Incoming request");
error_log("GET DIAGRAM: Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log("GET DIAGRAM: Requested ID: " . ($_GET['id'] ?? 'NOT SET'));

if (!isset($_SESSION['user_id'])) {
    error_log("GET DIAGRAM: User not authenticated");
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if (!isset($_GET['id'])) {
    error_log("GET DIAGRAM: Application ID missing");
    echo json_encode(['success' => false, 'error' => 'Application ID required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    error_log("GET DIAGRAM: Database connection successful");
    
    $stmt = $db->prepare('SELECT integration_diagram, integration_notes FROM applications WHERE id = ?');
    $stmt->execute([$_GET['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("GET DIAGRAM: Query executed");
    error_log("GET DIAGRAM: Found result: " . ($result ? 'YES' : 'NO'));
    
    if ($result) {
        error_log("GET DIAGRAM: Diagram code length: " . strlen($result['integration_diagram'] ?? ''));
        error_log("GET DIAGRAM: Notes length: " . strlen($result['integration_notes'] ?? ''));
        
        echo json_encode([
            'success' => true,
            'diagram_code' => $result['integration_diagram'] ?? '',
            'notes' => $result['integration_notes'] ?? ''
        ]);
    } else {
        error_log("GET DIAGRAM: Application not found");
        echo json_encode(['success' => false, 'error' => 'Application not found']);
    }
} catch (Exception $e) {
    error_log("GET DIAGRAM: Exception caught: " . $e->getMessage());
    error_log("GET DIAGRAM: Exception trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
