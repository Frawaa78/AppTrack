<?php
// public/api/get_integration_diagram.php
session_start();
require_once __DIR__ . '/../../src/db/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Application ID required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare('SELECT integration_diagram, integration_notes FROM applications WHERE id = ?');
    $stmt->execute([$_GET['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'diagram_code' => $result['integration_diagram'] ?? '',
            'notes' => $result['integration_notes'] ?? ''
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Application not found']);
    }
} catch (Exception $e) {
    error_log("Error fetching integration diagram: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
