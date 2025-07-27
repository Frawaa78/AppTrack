<?php
// Use absolute paths for one.com server
$rootPath = dirname(dirname(dirname(__FILE__)));
require_once $rootPath . '/src/db/db.php';
require_once $rootPath . '/src/config/config.php';

// No authentication required for testing
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_GET['application_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing application_id']);
    exit;
}

$application_id = intval($_GET['application_id']);

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("SELECT drawflow_diagram, drawflow_notes FROM applications WHERE id = ?");
    $stmt->execute([$application_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $diagram_data = $result['drawflow_diagram'] ? json_decode($result['drawflow_diagram'], true) : null;
        
        echo json_encode([
            'success' => true,
            'diagram_data' => $diagram_data,
            'notes' => $result['drawflow_notes']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Application not found']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
