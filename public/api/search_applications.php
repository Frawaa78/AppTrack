<?php
// public/api/search_applications.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../src/db/db.php';

try {
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    $exclude = isset($_GET['exclude']) ? (int)$_GET['exclude'] : 0;
    
    if (strlen($query) < 2) {
        echo json_encode([]);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Search in short_description and application_service
    $searchTerm = '%' . $query . '%';
    
    if ($exclude > 0) {
        $sql = "SELECT id, short_description, application_service 
                FROM applications 
                WHERE (short_description LIKE ? OR application_service LIKE ?) 
                AND id != ? 
                ORDER BY short_description LIMIT 20";
        $stmt = $db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $exclude]);
    } else {
        $sql = "SELECT id, short_description, application_service 
                FROM applications 
                WHERE (short_description LIKE ? OR application_service LIKE ?) 
                ORDER BY short_description LIMIT 20";
        $stmt = $db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm]);
    }
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format for Choices.js
    $choices = [];
    foreach ($results as $row) {
        $label = $row['short_description'];
        if (!empty($row['application_service'])) {
            $label .= ' (' . $row['application_service'] . ')';
        }
        
        $choices[] = [
            'value' => (string)$row['id'],
            'label' => $label,
            'customProperties' => [
                'description' => $row['short_description'],
                'service' => $row['application_service']
            ]
        ];
    }
    
    echo json_encode($choices);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Search failed: ' . $e->getMessage()]);
}
?>
